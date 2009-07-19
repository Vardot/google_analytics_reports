<?php
// $Id$

/**
 * Main form for query page.
 */
function google_analytics_api_query_form($form_state) {
  $form = array();

  $form['query']['info'] = array(
    '#value' => t('Please see ' . l('Google Analytics Data API Reference', 'http://code.google.com/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html') . ' for a list of dimensions and metrics.'),
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

/*
 * Construct a filter string that grabs pagePaths corresponding to the specified node or path.
 */
function _google_analytics_api_path_filter($node_path=null) {
	// Default path is the current path.
  if ($node_path == null)
	  $node_path = $_GET['q'];

  // Get the normal node path if it is a node.
	$node_path = drupal_get_normal_path($node_path);

  // Grab all aliases.
	$aliases = array($node_path);
	$result = db_query("SELECT * FROM {url_alias} WHERE src = '%s'", $node_path);
	while ($row = db_fetch_array($result))
	  $aliases[] = $row['dst'];

	// Add matching commands.
	$filters = array();
	foreach ($aliases as $alias)
		$filters[] = 'pagePath =~ ^/' . $alias . '([?#].*)?$';
  return implode(' || ', $filters);
}

/*
 * Block for showing statistics of the current path.
 */
function google_analytics_api_block($op='list', $delta=0) {
	switch ($op) {
		case 'list':
			$blocks[0]['info'] = t('Google Analytics Statistics');
			return $blocks;
		case 'view':
			$blocks['subject'] = t('Google Analytics Statistics');
			$blocks['content'] = google_analytics_api_current_summary();
			return $blocks;
	}
}

/*
 * Create a statistics summary block and return it.
 */
function google_analytics_api_current_summary() {
  $data = google_analytics_api_report_data(
		array('pagePath', 'date'),
		array('pageviews', 'timeOnPage', 'bounces', 'uniquePageviews'),
		array('date', 'pagePath'),
		_google_analytics_api_path_filter(),
		strtotime('-7 days'),
		time());

	// Add up all the statistics for different paths.
	$rows = array();
	foreach ($data as $item) {
		$date = strtotime($item->getDate());
		if (isset($rows[$date])) {
			foreach ($item->getMetrics() as $key => $value)
				$rows[$date][$key] += $value;
		} else {
		  $rows[$date] = $item->getMetrics();
	  }
	}

	// Format and perform calculations to display a table.
	$headers = array('Date', 'Page Views', 'Avg. Time', 'Bounce Rate');
	$theme_rows = array();
	foreach ($rows as $date => $row) {
		$date = date('m/d', $date);
		$page_views = $row['pageviews'];
		$avg_time = number_format($row['timeOnPage'] / $row['pageviews'], 1) . 's';
		$bounce_rate = number_format($row['bounces'] / $row['uniquePageviews'] * 100, 2) . '%';
		$theme_rows[] = array($date, $page_views, $avg_time, $bounce_rate);
	}

  return theme('table', $headers, $theme_rows);
}
