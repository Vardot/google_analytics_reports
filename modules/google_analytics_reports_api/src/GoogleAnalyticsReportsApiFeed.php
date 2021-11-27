<?php

namespace Drupal\google_analytics_reports_api;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheFactory;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Google\ApiCore\ApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Messenger\MessengerInterface;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;

/**
 * Class GoogleAnalyticsReportsApiFeed.
 *
 * GoogleAnalyticsReportsApiFeed class that acts
 * as a proxy for main google lib to call the API.
 */
class GoogleAnalyticsReportsApiFeed implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * Glib.
   *
   * @var object
   */
  public $client;

  /**
   * Boolean TRUE if data is from the cache tables.
   *
   * @var bool
   */
  public $fromCache = FALSE;

  /**
   * Property.
   *
   * @var string
   */
  public $property;

  /**
   * The cache factory.
   *
   * @var \Drupal\Core\Cache\CacheFactory
   */
  protected $cacheFactory;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The RequestStack service.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Google Analytics Reports Api Feed constructor.
   *
   * @param object|null $client
   *   The lib.
   * @param string|null $property
   *   The property id.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param Drupal\Core\Logger\LoggerChannelFactory $logger_factory
   *   The logger Factory.
   * @param Drupal\Core\Cache\CacheFactory $cache_factory
   *   The cache factory.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    $client = NULL,
    $property = NULL,
    ModuleHandlerInterface $module_handler = NULL,
    LoggerChannelFactory $logger_factory = NULL,
    CacheFactory $cache_factory = NULL,
    RequestStack $request_stack = NULL,
    TimeInterface $time = NULL,
    MessengerInterface $messenger = NULL
  ) {
    $this->client = $client;
    $this->property = $property;
    // phpcs:ignore
    $this->moduleHandler = $module_handler ? $module_handler : \Drupal::service('module_handler');
    // phpcs:ignore
    $this->loggerFactory = $logger_factory ? $logger_factory->get('google_analytics_reports_api') : \Drupal::service('cache_factory')->get('google_analytics_reports_api');
    // phpcs:ignore
    $this->cacheFactory = $cache_factory ? $cache_factory : \Drupal::service('cache_factory');
    // phpcs:ignore
    $this->requestStack = $request_stack ? $request_stack : \Drupal::service('request_stack');
    // phpcs:ignore
    $this->time = $time ? $time : \Drupal::service('datetime.time');
    // phpcs:ignore
    $this->messenger = $messenger ? $messenger : \Drupal::service('messenger');
  }

  /**
   * Instantiate a new GoogleAnalyticsReportsApiFeed object.
   * All API here can be called via this function https://developers.google.com/analytics/devguides/reporting/data/v1
   * Ex: GoogleAnalyticsReportsApiFeed::service()->runReport(['dateRange' => ...]);.
   *
   * All API here can be called via this function
   * https://developers.google.com/analytics/devguides/reporting/data/v1
   * Ex: GoogleAnalyticsReportsApiFeed::service()->runReport(['dateRange' => ...]);.
   *
   * @return object
   *   GoogleAnalyticsReportsApiFeed object to run needed API from
   *   new Analytics API.
   */
  public static function service($settings = [], $gclient = FALSE) {
    static $mclient;

    if (!$settings && isset($mclient)) {
      return $mclient;
    }

    try {
      $config = $settings
        ? $settings
        : \Drupal::configFactory()
          ->get('google_analytics_reports_api.settings')
          ->get();

      $absolute_path = '';
      if ($config['json'] ?? FALSE) {
        if (is_numeric($config['json'])) {
          $file = \Drupal::entityTypeManager()->getStorage('file')->load($config['json']);
          $uri = $file ? $file->getFileUri() : FALSE;
          $absolute_path = $file
            ? \Drupal::service('stream_wrapper_manager')
              ->getViaUri($uri)
              ->realpath()
            : FALSE;
        }
        else {
          $absolute_path = realpath($config['json']);
        }
      }

      if (!$absolute_path) {
        return FALSE;
      }

      putenv("GOOGLE_APPLICATION_CREDENTIALS={$absolute_path}");
      $property_id = $config['property'] ?? FALSE;

      if (!$property_id) {
        return FALSE;
      }
      $client = $gclient ? $gclient : new BetaAnalyticsDataClient();
      $mclient = new GoogleAnalyticsReportsApiFeed($client, $property_id);

      return $mclient;
    }
    catch (\Throwable $e) {
      // For the DrupalCI
      // fwrite(STDERR, print_r($e->getMessage(), TRUE));
      \Drupal::messenger()->addMessage(
        t('There was an authentication error. Message: @message.', [
          '@message' => $e->getMessage(),
        ]),
        'error',
        FALSE
      );
      \Drupal::logger('google_analytics_reports_api')->error(
        'There was an authentication error. Message: @message.',
        ['@message' => $e->getMessage()]
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      NULL,
      NULL,
      $container->get('module_handler'),
      $container->get('logger.factory')->get('google_analytics_reports_api'),
      $container->get('cache_factory'),
      $container->get('request_stack'),
      $container->get('datetime.time'),
      $container->get('messenger')
    );
  }

  /**
   * Sets the expiry timestamp for cached queries.
   *
   * Default is 3 days.
   *
   * @return int
   *   The UNIX timestamp to expire the query at.
   */
  public static function google_analytics_reports_api_cache_time() {
    return time() +
    \Drupal::config('google_analytics_reports_api.settings')->get(
      'cache_length'
    );
  }

  /**
   * Call a static drupal function.
   */
  public function __call($func, $params) {
    $icached = &drupal_static(__FUNCTION__);
    $tthis = $this;

    $bind = [
      'getMetadata' => static function () use ($tthis) {
        $property = $tthis->property;

        return $tthis->client->getMetadata("properties/{$property}/metadata");
      },
    ];

    $params = \is_array($params) ? $params : [];
    $params[0] = \is_array($params[0] ?? FALSE) ? $params[0] : [];
    $params[0] += ['property' => 'properties/' . $this->property];

    // Check if cache is available.
    $cache_options = [
      'cid' => NULL,
      'bin' => 'default',
      'expire' => self::google_analytics_reports_api_cache_time(),
      'refresh' => FALSE,
    ];

    if (empty($cache_options['cid'])) {
      $cache_options['cid'] =
        'google_analytics_reports_data:' .
        md5(serialize(array_merge($params, [$func])));
    }

    // Check for internal cached.
    if ($icached[$cache_options['bin']][$cache_options['cid']] ?? FALSE) {
      return $icached[$cache_options['bin']][$cache_options['cid']];
    }

    // Check for DB cache.
    $cache = $this->cacheFactory
      ->get($cache_options['bin']);
    $cache = $cache ? $cache->get($cache_options['cid']) : FALSE;

    if (
      !$cache_options['refresh']
      && isset($cache)
      && !empty($cache->data)
      && $cache->expire > $this->time->getRequestTime()
    ) {
      $this->fromCache = TRUE;

      return $cache->data;
    }

    try {
      if (\in_array($func, array_keys($bind), TRUE)) {
        $ret = \call_user_func_array($bind[$func], $params);
      }
      else {
        // Add property to the rest.
        $ret = \call_user_func_array([$this->client, $func], $params);
      }
    }
    catch (ApiException $e) {
      $this->messenger->addMessage($this->t('Error occurred! @e', ['@e' => $e]), 'error');

      return FALSE;
    }

    if ($ret) {
      $icached[$cache_options['bin']][$cache_options['cid']] = $ret;
      $this->cacheFactory
        ->get($cache_options['bin'])
        ->set($cache_options['cid'], $ret, $cache_options['expire']);
    }

    return $ret;
  }

  /**
   * Check if object is authenticated with Google.
   */
  public function isAuthenticated() {
    // @todo Validate the json file here
    return !empty($this->client);
  }

}
