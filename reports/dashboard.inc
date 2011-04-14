<?php

/**
 * @file
 * Collecting report queries for the dashboard block.
 * UI will look like http://s.wordpress.org/extend/plugins/google-analyticator/screenshot-1.png?r=371673
 *
 * @FIXME - this file is probably temporary - code to be renamed / integrated elsewhere
 *
 */

/**
 * Renders an img element with a chart of the number of visits over the past 30 days.
 */
function google_analytics_reports_visits() {
  $params = array(
    'metrics' => array('ga:visits'),
    'dimensions' => array('ga:date'),
    'sort_metric' => array('ga:date'),
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
  );
  $GAFeed = google_analytics_api_report_data($params);
  if ($GAFeed->error) {
    return 'oops'; // @FIXME
  }
  $max_visits = 0;
  foreach ($GAFeed->results as $row) {
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
  $last_day = end($GAFeed->results);
  $title = t('The most visits on a single day was @max.  Yesterday there were @yesterday visits.', array('@max' => $max_visits, '@yesterday' => $last_day['visits']));
  $output = chart_render($chart, array('title' => $title, 'style' => 'height:' . $chart['#size']['#height'] . 'px; width:100%'));
  return $output;
}

/**
 *
 */
function google_analytics_reports_usage() {
  // Site usage
  $params = array(
    'metrics' => array('ga:visits', 'ga:bounces', 'ga:entrances', 'ga:pageviews', 'ga:timeOnSite', 'ga:newVisits'),
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
  );
  $GAFeed = google_analytics_api_report_data($params);
  if ($GAFeed->error) {
    return 'oops'; // @FIXME
  }
  return $GAFeed->results[0]; // @FIXME - check_plain this data
}

function google_analytics_reports_top_pages() {
  // Top pages
  $params = array(
    'metrics' => array('ga:pageviews'),
    'dimensions' => array('ga:pageTitle', 'ga:pagePath'),
    'sort_metric' => array('-ga:pageviews'),
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
    'sort' => '-ga:pageviews',
    'max_results' => 5,
  );
  $GAFeed = google_analytics_api_report_data($params);
  if ($GAFeed->error) {
    return 'oops'; // @FIXME
  }
  return $GAFeed->results; // @FIXME - check_plain this data
}

function google_analytics_reports_top_referrals() {
  $params = array(
    'metrics' => array('ga:visits'),
    'dimensions' => array('ga:source', 'ga:medium'),
    'sort_metric' => array('-ga:visits'),
    'filters' => 'ga:medium==referral',
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
    'max_results' => 5,
  );
  $GAFeed = google_analytics_api_report_data($params);
  if ($GAFeed->error) {
    return 'oops'; // @FIXME
  }
  return $GAFeed->results; // @FIXME - check_plain this data
}

function google_analytics_reports_top_searches() {
  $params = array(
    'metrics' => array('ga:visits'),
    'dimensions' => array('ga:keyword'),
    'sort_metric' => array('-ga:visits'),
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
    'filters' => 'ga:keyword!=(not set)',
    'max_results' => 5,
  );
  $GAFeed = google_analytics_api_report_data($params);
  if ($GAFeed->error) {
    return 'oops'; // @FIXME
  }
  return $GAFeed->results; // @FIXME - check_plain this data
}