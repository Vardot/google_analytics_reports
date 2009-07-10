<?php
// $Id$

/**
 * Main form for query page.
 */
function google_analytics_api_query_form($form_state) {
  $form = array();

  $form['query']['info'] = array(
    '#value' => t('Please see ' . l('Google Analytics Data API Reference', 'http://code.google.com/apis/analytics/docs/gdata/gdataReference.html') . ' for a list of dimensions and metrics.'),
  );
  $form['query']['dimensions'] = array(
    '#type' => 'textfield',
    '#title' => t('Dimensions'),
    '#description' => t('(browser, browserVersion, city, connectionSpeed, continent, date, hostname, pagePath, pageTitle...)'),
  );
  $form['query']['metrics'] = array(
    '#type' => 'textfield',
    '#title' => t('Metrics'),
    '#description' => t('(entrances, pageviews, timeOnPage, visitors, visits...)'),
  );
  $form['query']['sort_metric'] = array(
    '#type' => 'textfield',
    '#title' => t('Sort Metric'),
    '#description' => t('(e.g. -entrances, pageviews)'),
  );
  $form['query']['filter'] = array(
    '#type' => 'textfield',
    '#title' => t('Filter'),
    '#description' => t('(e.g. country == United States && browser == Firefox || browser == Chrome)'),
  );
  $form['query']['start_date'] = array(
    '#type' => 'date',
    '#title' => t('Start Date'),
  );
  $form['query']['no_start_date'] = array(
    '#type' => 'checkbox',
    '#title' => t('No start date.'),
    '#default_value' => 1,
    '#description' => t('(Default: Google Analytics launch date of 1 Jan 2005)'),
  );
  $form['query']['end_date'] = array(
    '#type' => 'date',
    '#title' => t('End Date'),
  );
  $form['query']['no_end_date'] = array(
    '#type' => 'checkbox',
    '#title' => t('No end date.'),
    '#default_value' => 1,
    '#description' => t('(Default: Today)'),
  );
  $form['query']['start_index'] = array(
    '#type' => 'textfield',
    '#title' => t('Start Index'),
    '#default_value' => 1,
  );
  $form['query']['max_results'] = array(
    '#type' => 'textfield',
    '#title' => t('Maximum Results'),
    '#default_value' => 30,
  );
  $form['buttons']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit query'),
  );
  $form['#redirect'] = FALSE;

  if (!empty($form_state['post'])) {
    $form['results'] = array(
      '#type' => 'item',
      '#title' => t('Query results'),
      '#value' => _google_analytics_api_query_form_fetch_results($form_state),
    );
  }

  return $form;
}

function _google_analytics_api_query_form_fetch_results($form_state = array()) {
  $dimensions = _google_analytics_api_query_form_process($form_state['post']['dimensions']);
  $metrics = _google_analytics_api_query_form_process($form_state['post']['metrics']);
  $sort_metric = _google_analytics_api_query_form_process($form_state['post']['sort_metric']);
  $filter = $form_state['post']['filter'];
  $start_date = null;
  if ($form_state['post']['no_start_date'] != '1') {
    $start_date =
      $form_state['post']['start_date']['year'] . '-' .
      str_pad($form_state['post']['start_date']['month'], 2, '0', STR_PAD_LEFT) . '-' .
      str_pad($form_state['post']['start_date']['day'], 2, '0', STR_PAD_LEFT);
  }
  $end_date = null;
  if ($form_state['post']['no_end_date'] != '1') {
    $end_date =
      $form_state['post']['end_date']['year'] . '-' .
      str_pad($form_state['post']['end_date']['month'], 2, '0', STR_PAD_LEFT) . '-' .
      str_pad($form_state['post']['end_date']['day'], 2, '0', STR_PAD_LEFT);
  }
  $start_index = $form_state['post']['start_index'];
  $max_results = $form_state['post']['max_results'];
  try {
    $entries = google_analytics_api_report_data(
      $dimensions, $metrics, $sort_metric, $filter,
      $start_date, $end_date, $start_index, $max_results);
  } catch (Exception $e) {
    return $e->getMessage();
  }

  $header = array_merge($dimensions, $metrics);

  $rows = array();
  foreach ($entries as $entry) {
    $row = array();
    $row = array_merge($row, $entry->getDimensions());
    $row = array_merge($row, $entry->getMetrics());
    $rows[] = $row;
  }
  $totals = array(t('Aggregate Value'));
  $totals = array_pad($totals, sizeof($dimensions), '');
  $totals = array_merge($totals, google_analytics_api_gapi()->getMetrics());
  $rows[] = $totals;

  return theme('table', $header, $rows);
}

/**
 * Process a query string.
 */
function _google_analytics_api_query_form_process($string) {
  $array = explode(',', $string);
  $return = array();
  foreach ($array as $value)
    if ($value = trim($value))
      $return[] = $value;
  return $return;
}

/**
 * The admin form.
 */
function google_analytics_api_admin() {
  $form = array();

  if (google_analytics_api_check_token()) {
    $form['login_status'] = array(
      '#value' => t('Currently you are logged in. '),
      '#weight' => -10,
    );
    $form['logout'] = array(
      '#type' => 'submit',
      '#value' => t('Logout'),
      '#weight' => -5,
    );
    $form['profile_id'] = array(
      '#type' => 'select',
      '#title' => t('Profile'),
      '#options' => google_analytics_api_admin_account_data_options(),
      '#default_value' => variable_get('google_analytics_api_profile_id', 0),
      '#description' => t('Choose your website\'s profile'),
      '#required' => TRUE,
      '#weight' => -2,
    );
    /*$profile = google_analytics_api_current_profile();
    $date = strtotime($profile->getUpdated());
    $form['profile_status'] = array(
      '#value' => '<div><em>(Profile last updated ' . date('F j, Y h:i:s A', $date) . ')</em></div>',
      '#weight' => -1,
    );*/
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#weight' => 3,
    );
  } else {
    $form['logout_status'] = array(
      '#value' => t('Please ' . l('Log In', '/admin/settings/google_analytics_api') . ' before trying to access statistics.'),
      '#weight' => -10,
    );
    $form['buttons']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Login to a Google account'),
    );
  }

  return $form;
}

/*
 * Returns an associative array of profile IDs and their names for an account.
 */
function google_analytics_api_admin_account_data_options() {
    $accounts = google_analytics_api_account_data();
    $options = array();
    $i = 0;
    foreach ($accounts as $account)
      $options[$account->getProfileId()] = $account->getTitle() . ' (' . $account->getProfileId() . ')';
    return $options;
}

/*
 * Admin form callback, run on submission of any buttons.
 */
function google_analytics_api_admin_submit($form, &$form_state) {
  if (variable_get('google_analytics_api_token', null)) {
    if ($form_state['clicked_button']['#value'] == 'Logout') {
      drupal_set_message(t('You have been logged out of your Google account.'));
      return google_analytics_api_logout();
    } elseif ($form_state['clicked_button']['#value'] == 'Save configuration') {
      variable_set('google_analytics_api_profile_id', intval($form_state['values']['profile_id']));
    }
  } else {
    return google_analytics_api_authenticate();
  }
}
