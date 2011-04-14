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
  // return;
  $params = array(
    'metrics' => array('visits'),
    'dimensions' => array('date'),
    'sort_metric' => array('date'),
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
    '#type' => CHART_TYPE_LINE,
    '#size' => chart_size(800, 300),
    '#adjust_resolution' => TRUE,
    '#data_colors' => array('027AC6'),
    '#chart_fill' => chart_fill('bg', 'EDF5FA'),
    '#line_styles' => array(chart_line_style(3, 10, 0)),
    '#shape_markers' => array(chart_shape_marker(0, -1, 'o', 6, '027AC6')),
    '#grid_lines' => chart_grid_lines(0, 20, 1, 3),
  );
  $chart['mixed_axis_labels'][CHART_AXIS_Y_LEFT][0][] = chart_mixed_axis_range_label(0, max($data));
  foreach ($x_labels as $label) {
    $chart['mixed_axis_labels'][CHART_AXIS_X_BOTTOM][1][] = chart_mixed_axis_label($label);
  }
  $last_day = end($GAFeed->results);
  $title = t('The most visits on a single day was @max.  Yesterday there were @yesterday visits.', array('@max' => $max_visits, '@yesterday' => $last_day['visits']));
  $output = chart_render($chart, array('title' => $title));
  return $output;
}

/**
 *
 */
function google_analytics_reports_usage() {
  // Site usage
  $params = array(
    'metrics' => array('visits', 'bounces', 'entrances', 'pageviews', 'timeOnSite', 'newVisits'),
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
    'metrics' => array('pageviews'),
    'dimensions' => array('pageTitle', 'pagePath'),
    'sort_metric' => array('-pageviews'),
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
    'sort' => '-pageviews',
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
    'metrics' => array('visits'),
    'dimensions' => array('source', 'medium'),
    'sort_metric' => array('-visits'),
    'filter' => 'medium==referral',
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
    'metrics' => array('visits'),
    'dimensions' => array('keyword'),
    'sort_metric' => array('-visits'),
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
    'filter' => 'keyword!=(not set)',
    'max_results' => 5,
  );
  $GAFeed = google_analytics_api_report_data($params);
  if ($GAFeed->error) {
    return 'oops'; // @FIXME
  }
  return $GAFeed->results; // @FIXME - check_plain this data
}