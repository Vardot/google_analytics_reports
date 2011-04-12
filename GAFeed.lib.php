<?php

/**
 * @file
 * Provides the GAFeed object type and associated methods.
 */

/**
 * GAFeed class to authorize access to and request data from
 * the Google Analytics Data Export API.
 */
class GAFeed {

  /* Methods require at least v2 */
  const gaFeedVersion = 2;

  /* Response object */
  public $response;

  /* Formatted array of request results */
  public $results;

  /* Formatted array of request meta info */
  public $meta;

  /* URL to Google Analytics Data Export API */
  public $queryPath;

  /* Aggregate numbers from response */
  public $totals;

  /* Translated error message */
  public $error;

  /* Boolean TRUE if data is from the cache tables */
  public $fromCache = FALSE;

  /* Domain of Data Feed API */
  protected $host = 'www.google.com';

  /* Request header source */
  protected $source = 'drupal';

  /* Default is HMAC-SHA1 */
  protected $signatureMethod;

  /* HMAC-SHA1 Consumer data */
  protected $consumer;

  /* OAuth token */
  protected $token;

  /* Google authorize callback verifier string */
  protected $verifier;

  /**
   * Constructor for the GAFeed class
   */
  public function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {

    /* Drupal OAuth module is required */
    module_load_include('lib.php', 'oauth');
    $this->signatureMethod = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);

    /* Allow developers the option of OAuth authentication without using this class's methods */
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    }
  }

  /**
   * Set the verifier property
   */
  public function setVerifier($verifier) {
    $this->verifier = $verifier;
  }

  /**
   * Set the host property
   */
  public function setHost($host) {
    $this->host = $host;
  }

  /**
   * Set the queryPath property
   */
  protected function setQueryPath($path) {
    $this->queryPath = 'https://'. $this->host .'/'. $path;
  }

  /**
   * OAuth step #1: Fetch request token.
   */
  public function getRequestToken() {
    $this->setQueryPath('accounts/OAuthGetRequestToken');

    /* xoauth_displayname is displayed on the Google Authentication page */
    $params = array(
      'scope' => 'https://www.google.com/analytics/feeds',
      'oauth_callback' => url('google-analytics-reports/oauth', array('absolute' => TRUE)),
      'xoauth_displayname' => t('Google Analytics Reports Drupal module'),
    );

    $this->query($this->queryPath, $params, 'GET', array('refresh' => TRUE));
    parse_str($this->response->data, $token);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * OAuth step #2: Authorize request token.
   */
  public function obtainAuthorization($token) {
    $this->setQueryPath('accounts/OAuthAuthorizeToken');

    /* hd is the best way of dealing with users with multiple domains verified with Google */
    $params = array(
      'oauth_token' => $token['oauth_token'],
      'hd' => variable_get('google_analytics_reports_hd', 'default'),
    );

    drupal_goto($this->queryPath, $params);
  }

  /**
   * OAuth step #3: Fetch access token.
   */
  public function getAccessToken() {
    $this->setQueryPath('accounts/OAuthGetAccessToken');

    $params = array(
      'oauth_verifier' => $this->verifier,
    );

    $this->query($this->queryPath, $params, 'GET', array('refresh' => TRUE));
    parse_str($this->response->data, $token);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * Revoke OAuth token.
   */
  public function revokeToken() {
    $this->setQueryPath('accounts/AuthSubRevokeToken');
    $this->query($this->queryPath, array(), 'GET', array('refresh' => TRUE));
  }

  /**
   * Public query method for all Data Export API features.
   */
  public function query($path, $params = array(), $method = 'GET', $cache_options = array()) {

    $params_defaults = array(
      'v' => self::gaFeedVersion,
    );
    $params += $params_defaults;

    /* Provide cache defaults if a developer did not override them */
    $cache_defaults = array(
      'cid' => NULL,
      'expire' => CACHE_TEMPORARY,
      'refresh' => FALSE,
    );
    $cache_options += $cache_defaults;

    /* Provide a query MD5 for the cid if the developer did not provide one */
    if (empty($cache_options['cid'])) {
      $cache_options['cid'] = 'GAFeed:' . md5(serialize(array_merge($params, array($path, $method))));
    }

    $cache = cache_get($cache_options['cid']);

    if (!$cache_options['refresh'] && isset($cache) && !empty($cache->data)) {
      $this->response = $cache->data;
      $this->fromCache = TRUE;
    }
    else {
      $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $this->queryPath, $params);
      $request->sign_request($this->signatureMethod, $this->consumer, $this->token);
      switch ($method) {
        case 'GET':
          $this->request($request->to_url());
          break;
        case 'POST':
          $this->request($request->get_normalized_http_url(), $request->get_parameters(), 'POST');
          break;
      }

      /* Do not cache erroneous queries */
      if (empty($this->error)) {
        cache_set($cache_options['cid'], $this->response, 'cache', $cache_options['refresh']);
      }
    }

    return (empty($this->error));
  }

  /**
   * Execute a query
   */
  protected function request($url, $params = array(), $method = 'GET') {
    $data = '';
    if (count($params) > 0) {
      if ($method == 'GET') {
        $url .= '?'. http_build_query($params, '', '&');
      }
      else {
        $data = http_build_query($params, '', '&');
      }
    }

    $headers = array();
    $this->response = drupal_http_request($url, $headers, $method, $data);

    if ($this->response->code != '200') {
      $error_msg = 'Code: !code - Error: !message - Message: !details';
      $error_vars = array('!code' => $this->response->code, '!message' => $this->response->error, '!details' => strip_tags($this->response->data));
      $this->error =  t($error_msg, $error_vars);
      watchdog('google analytics reports', $error_msg, $error_vars, WATCHDOG_ERROR);
    }
  }

  /**
   * Query and sanitize account data
   */
  public function queryAccountFeed($params = array(), $cache_options = array()) {

    $params  += array(
      'start-index' => 1,
      'max-results' => 20,
    );

    $this->setQueryPath('analytics/feeds/accounts/default');
    if ($this->query($this->queryPath, $params, 'GET', $cache_options)) {
      $this->sanitizeAccount();
    }
  }

  /**
   * Sanitize account data
   */
  protected function sanitizeAccount() {
    $xml = simplexml_load_string($this->response->data);

    $this->results = NULL;
    $results = array();
    $meta = array();

    /* Save meta info */
    $meta['updated'] = strval($xml->updated);
    $meta['generator'] = strval($xml->generator);
    $meta['generatorVersion'] = strval($xml->generator->attributes());

    $opensearch = $xml->children('http://a9.com/-/spec/opensearchrss/1.0/');
    foreach ($opensearch as $key => $open_search_result) {
      $meta[$key] = intval($open_search_result);
    }

    /* Save results */
    foreach ($xml->entry as $entry) {
      $properties = array();
      foreach ($entry->children('http://schemas.google.com/analytics/2009')->property as $property) {
        $properties[str_replace('ga:', '', $property->attributes()->name)] = strval($property->attributes()->value);
      }
      $properties['title'] = strval($entry->title);
      $properties['updated'] = strval($entry->updated);
      $results[$properties['profileId']] = $properties;
    }

    $this->meta = $meta;
    $this->results = $results;
  }

  /**
   * Query and sanitize report data
   */
  public function queryReportFeed($params = array(), $cache_options = array()) {

    /* Provide defaults if the developer did not override them */
    $params += array(
      'profile_id' => 0,
      'dimensions' => NULL,
      'metrics' => 'visits',
      'sort_metric' => '-visits',
      'filter' => NULL,
      'segment' => NULL,
      'start_date' => NULL,
      'end_date' => NULL,
      'start_index' => 1,
      'max_results' => 10000,
    );

    $parameters = array('ids' => 'ga:' . $params['profile_id']);

    if (is_array($params['dimensions'])) {
      $dimensions_string = '';
      foreach ($params['dimensions'] as $dimension) {
        $dimensions_string .= ',ga:' . $dimension;
      }
      $parameters['dimensions'] = drupal_substr($dimensions_string, 1);
    }
    elseif ($params['dimensions'] !== NULL) {
      $parameters['dimensions'] = 'ga:' . $params['dimensions'];
    }

    if (is_array($params['metrics'])) {
      $metrics_string = '';
      foreach ($params['metrics'] as $metric) {
        $metrics_string .= ',ga:' . $metric;
      }
      $parameters['metrics'] = drupal_substr($metrics_string, 1);
    }
    else {
      $parameters['metrics'] = 'ga:' . $params['metrics'];
    }

    if ($params['sort_metric'] == NULL && isset($parameters['metrics'])) {
      $parameters['sort'] = $parameters['metrics'];
    }
    elseif (is_array($params['sort_metric'])) {
      $sort_metric_string = '';

      foreach ($params['sort_metric'] as $sort_metric_value) {
        if (drupal_substr($sort_metric_value, 0, 1) == "-") {
          $sort_metric_string .= ',-ga:' . drupal_substr($sort_metric_value, 1); // Descending
        }
        else {
          $sort_metric_string .= ',ga:' . $sort_metric_value; // Ascending
        }
      }
      $parameters['sort'] = drupal_substr($sort_metric_string, 1);
    }
    else {
      if (drupal_substr($params['sort_metric'], 0, 1) == "-") {
        $parameters['sort'] = '-ga:' . drupal_substr($params['sort_metric'], 1);
      }
      else {
        $parameters['sort'] = 'ga:' . $params['sort_metric'];
      }
    }

    if ($params['filter'] != NULL) {
      $filter = $this->processFilter($params['filter']);
      if ($filter !== FALSE) {
        $parameters['filters'] = $filter;
      }
    }

    if ($params['segment'] !== NULL) {
      if (is_int($params['segment'])) {
        $parameters['segment'] = 'gaid::' . $params['segment'];
      }
      else {
        $segment = $this->processFilter($params['segment']);
        if ($segment !== FALSE) {
          $parameters['segment'] = 'dynamic::' . $segment;
        }
      }
    }

    if ($params['start_date'] == NULL) {
      /* Use the day that Google Analytics was released (1 Jan 2005) */
      $start_date = '2005-01-01';
    }
    elseif (is_int($params['start_date'])) {
      /* Assume a Unix timestamp */
      $start_date = date('Y-m-d', $params['start_date']);
    }

    $parameters['start-date'] = $start_date;

    if ($params['end_date'] == NULL) {
      $end_date = date('Y-m-d');
    }
    elseif (is_int($params['end_date'])) {
      /* Assume a Unix timestamp */
      $end_date = date('Y-m-d', $params['end_date']);
    }

    $parameters['end-date'] = $end_date;

    $parameters['start-index'] = $params['start_index'];
    $parameters['max-results'] = $params['max_results'];

    $this->setQueryPath('analytics/feeds/data');
    if ($this->query($this->queryPath, $parameters, 'GET', $cache_options)) {
      $this->sanitizeReport();
    }
  }

  /**
   * Sanitize report data
   */
  protected function sanitizeReport() {
    $xml = simplexml_load_string($this->response->data);

    $this->results = NULL;
    $results = array();
    $meta = array();
    $totals = array();


    /* Save meta info */
    $meta['updated'] = strval($xml->updated);
    $meta['generator'] = strval($xml->generator);
    $meta['generatorVersion'] = strval($xml->generator->attributes());

    $opensearch = $xml->children('http://a9.com/-/spec/opensearchrss/1.0/');
    foreach ($opensearch as $key => $open_search_result) {
      $meta[$key] = intval($open_search_result);
    }

    $google_results = $xml->children('http://schemas.google.com/analytics/2009');
    foreach ($google_results->dataSource->property as $property_attributes) {
      $meta[str_replace('ga:', '', $property_attributes->attributes()->name)] = strval($property_attributes->attributes()->value);
    }
    $meta['startDate'] = strval($google_results->startDate);
    $meta['endDate'] = strval($google_results->endDate);

    /* Save totals */
    foreach ($google_results->aggregates->metric as $aggregate_metric) {
      $metric_value = strval($aggregate_metric->attributes()->value);
      /* Check for float, or value with scientific notation */
      if (preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/', $metric_value)) {
        $totals[str_replace('ga:', '', $aggregate_metric->attributes()->name)] = floatval($metric_value);
      }
      else {
        $totals[str_replace('ga:', '', $aggregate_metric->attributes()->name)] = intval($metric_value);
      }
    }

    /* Save results */
    foreach ($xml->entry as $entry) {
      $metrics = array();
      foreach ($entry->children('http://schemas.google.com/analytics/2009')->metric as $metric) {
        $metric_value = strval($metric->attributes()->value);

        //Check for float, or value with scientific notation
        if (preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/', $metric_value)) {
          $metrics[str_replace('ga:', '', $metric->attributes()->name)] = floatval($metric_value);
        }
        else {
          $metrics[str_replace('ga:', '', $metric->attributes()->name)] = intval($metric_value);
        }
      }

      $dimensions = array();
      foreach ($entry->children('http://schemas.google.com/analytics/2009')->dimension as $dimension) {
        $dimensions[str_replace('ga:', '', $dimension->attributes()->name)] = strval($dimension->attributes()->value);
      }

      $results[] = array_merge($metrics, $dimensions);
    }

    $this->meta = $meta;
    $this->totals = $totals;
    $this->results = $results;
  }

  /**
   * Format filter query data to be compatible with
   * the Data Feed API.
   */
  protected function processFilter($filter) {
    $valid_operators = '(!~|=~|==|!=|>|<|>=|<=|=@|!@)';

    $filter = preg_replace('/\s\s+/', ' ', trim($filter)); //Clean duplicate whitespace
    $filter = str_replace(array(',', ';'), array('\,', '\;'), $filter); //Escape Google Analytics reserved characters
    $filter = preg_replace('/(&&\s*|\|\|\s*|^)([a-zA-Z0-9]+)(\s*' . $valid_operators . ')/i', '$1ga:$2$3', $filter); //Prefix ga: to metrics and dimensions
    $filter = preg_replace('/[\'\"]/i', '', $filter); //Clear invalid quote characters
    $filter = preg_replace(array('/\s*&&\s*/', '/\s*\|\|\s*/','/\s*' . $valid_operators . '\s*/'), array(';', ',', '$1'), $filter); //Clean up operators

    if (drupal_strlen($filter) > 0) {
      return urlencode($filter);
    }
    else {
      return FALSE;
    }
  }
}