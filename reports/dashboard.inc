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
 * Returns HTML with a chart of the number of visits over the past 30 days.
 */
function google_analytics_reports_visits() {
  $request = array(
    '#metrics' => array('visits'),
    '#dimensions' => array('date'),
    '#sort_metric' => array('date'),
    '#start_date' => strtotime('-31 days'),
    '#end_date' => strtotime('-1 day'),
  );
  $rows = google_analytics_api_report_data($request);
  if (empty($rows)) {
    return '<p>' . t('N/A') . '</p>'; // @TODO
  }
  $chart_visits = array();
  $chart_dates = array();
  dpm($rows); return;
  foreach ($rows as $date => $row) {
    $chart_pageviews[] = $row['pageviews'];
    $chart_dates[] = date('M d', strtotime($date));
  }
  $visits_chart = _google_analytics_reports_sparkline_chart('visits', '', $chart_visits, $chart_dates);
  return chart_render($visits_chart);
}


// Site usage
$request = array(
  '#metrics' => array('visits,bounces,entrances,pageviews,timeOnSite,newVisits'),
  '#start_date' => strtotime('-31 days'),
  '#end_date' => strtotime('-1 day'),
);

// Top pages
$request = array(
  '#metrics' => array('pageviews'),
  '#dimensions' => array('pageTitle,pagePath'),
  '#sort_metric' => array('date'),
  '#start_date' => strtotime('-31 days'),
  '#end_date' => strtotime('-1 day'),
  '#sort' => '-pageviews',
  '#max_results' => 100,
);

// Top referers
$request = array(
  '#metrics' => array('visits'),
  '#dimensions' => array('source,medium'),
  '#sort_metric' => array('-visits'),
  '#start_date' => strtotime('-31 days'),
  '#end_date' => strtotime('-1 day'),
  '#max_results' => 100,
);

// Top searches
$request = array(
  '#metrics' => array('visits'),
  '#dimensions' => array('keyword'),
  '#sort_metric' => array('-visits'),
  '#start_date' => strtotime('-31 days'),
  '#end_date' => strtotime('-1 day'),
  '#filter' => 'keyword!%3D(not%20set)',
  '#max_results' => 100,
);