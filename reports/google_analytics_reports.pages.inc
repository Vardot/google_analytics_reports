<?php
/**
 * @file
 * Page callbacks for google analytics.
 */

/**
 * Page callback for admin/reports/google-analytics-report.
 *
 * @return An HTML summary of the site-wide statistics.
 */
function google_analytics_reports_summary_page() {
  if (!variable_get('google_analytics_reports_oauth_token', FALSE)) {
    drupal_set_message(t('You must <a href="!url">authorize</a> Drupal to use your Analytics account before you can view reports.', array('!url' => url('admin/settings/google-analytics-reports'))), 'warning');
    return ' ';
  }

  $summary = array(
    'visit_chart' => _google_analytics_reports_visits(),
    'usage' => _google_analytics_reports_usage(),
    'pages' => _google_analytics_reports_top_pages(),
    'referrals' => _google_analytics_reports_top_referrals(),
    'searches' => _google_analytics_reports_top_searches(),
  );

  if (in_array(FALSE, $summary)) {
    drupal_set_message(t('There was a problem retrieving the statistics.  Please review the watchdog for details.'), 'error');
    return ' ';
  }

  return theme('google_analytics_reports_summary', $summary);
}

/**
 * Renders an img element with a chart of the number of visits over the past 30 days.
 */
function _google_analytics_reports_visits() {
  $params = array(
    'metrics' => array('ga:visits'),
    'dimensions' => array('ga:date'),
    'sort_metric' => array('ga:date'),
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
  );
  $feed = google_analytics_api_report_data($params);
  if ($feed->error) {
    return FALSE;
  }
  $max_visits = 0;
  foreach ($feed->results as $row) {
    $data[] = $row['visits'];
    $x_labels[] = date('M d', strtotime($row['date']));
    $max_visits = max($row['visits'], $max_visits);
  }
  $chart = array(
    '#chart_id' => 'pageviews_2weeks',
    '#data' => $data,
    '#type' => CHART_TYPE_LINE . ':nda',
    '#size' => chart_size(1000, 80),
    '#adjust_resolution' => TRUE,
    '#data_colors' => array('AAAAAA'),
    '#chart_fill' => chart_fill('bg', '00000000'),
    '#shape_markers' => array(chart_shape_marker(0, 0, 'B', 0, $color = 'EEEEEE')),
    '#line_styles' => array(chart_line_style(2, 10, 0)),
  );
  $chart['mixed_axis_labels'][CHART_AXIS_Y_LEFT][0][] = chart_mixed_axis_range_label(0, max($data));
  foreach ($x_labels as $label) {
    $chart['mixed_axis_labels'][CHART_AXIS_X_BOTTOM][1][] = chart_mixed_axis_label($label);
  }
  $last_day = end($feed->results);
  $title = t('The most visits on a single day was @max.  Yesterday there were @yesterday visits.', array('@max' => $max_visits, '@yesterday' => $last_day['visits']));
  $output = chart_render($chart, array('title' => $title, 'style' => 'height:' . $chart['#size']['#height'] . 'px; width:100%'));
  return $output;
}

/**
 * Retrieves usage data.
 */
function _google_analytics_reports_usage() {
  // Site usage
  $params = array(
    'metrics' => array('ga:visits', 'ga:bounces', 'ga:entrances', 'ga:pageviews', 'ga:timeOnSite', 'ga:newVisits'),
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
  );
  $feed = google_analytics_api_report_data($params);
  if ($feed->error) {
    return FALSE;
  }
  return $feed->results[0];
}

/**
 * Retrieves top pages.
 */
function _google_analytics_reports_top_pages() {
  // Top pages
  $params = array(
    'metrics' => array('ga:pageviews'),
    'dimensions' => array('ga:pageTitle', 'ga:hostname', 'ga:pagePath'),
    'sort_metric' => array('-ga:pageviews'),
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
    'sort' => '-ga:pageviews',
    'max_results' => 5,
  );
  $feed = google_analytics_api_report_data($params);
  if ($feed->error) {
    return FALSE;
  }
  return $feed->results;
}

/**
 * Retrieves top referrals.
 */
function _google_analytics_reports_top_referrals() {
  $params = array(
    'metrics' => array('ga:visits'),
    'dimensions' => array('ga:source', 'ga:medium'),
    'sort_metric' => array('-ga:visits'),
    'filters' => 'ga:medium==referral',
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
    'max_results' => 5,
  );
  $feed = google_analytics_api_report_data($params);
  if ($feed->error) {
    return FALSE;
  }
  return $feed->results;
}

/**
 * Retrieves top searches.
 */
function _google_analytics_reports_top_searches() {
  $params = array(
    'metrics' => array('ga:visits'),
    'dimensions' => array('ga:keyword'),
    'sort_metric' => array('-ga:visits'),
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
    'filters' => 'ga:keyword!=(not set)',
    'max_results' => 5,
  );
  $feed = google_analytics_api_report_data($params);
  if ($feed->error) {
    return FALSE;
  }
  return $feed->results;
}