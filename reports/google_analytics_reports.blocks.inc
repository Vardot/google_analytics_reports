<?php

/**
 * Generates a block with the current page statistics.
 */
function _google_analytics_reports_page_block() {
  // @TODO - display this block with AHAH, because the API query can add a lot of latency.

  // Ensure user has permission to view this block.
  if (!user_access('access google analytics reports')) {
    return FALSE;
  }

  if (!variable_get('google_analytics_reports_oauth_token', FALSE)) {
    return '<p>' . t('You must <a href="!url">authorize</a> Drupal to use your Analytics account before you can view reports.', array('!url' => url('admin/settings/google-analytics-reports'))) . '</p>';
  }

  // @TODO - consider aliases
  $page = $_SERVER['REQUEST_URI'];
  if ($page == '/') {
    $page = '/index.html';
  }

  $params = array(
    'metrics' => array('ga:pageviews'),
    'dimensions' => array('ga:date'),
    'sort_metric' => array('ga:date'),
    'filters' => "ga:pagePath==$page",
    'start_date' => strtotime('-31 days'),
    'end_date' => strtotime('-1 day'),
  );
  $feed = google_analytics_api_report_data($params);
  if ($feed->error) {
    return '<p>' . t('There was a problem retrieving the statistics.  Please review the watchdog for details.') . '</p>';
  }
  $max_visits = 0;
  foreach ($feed->results as $row) {
    $data[] = $row['pageviews'];
    $max_visits = max($row['pageviews'], $max_visits);
  }
  $chart = array(
    '#chart_id' => 'pageviews_2weeks',
    '#data' => $data,
    '#type' => CHART_TYPE_LINE . ':nda',
    '#size' => chart_size(500, 40),
    '#adjust_resolution' => TRUE,
    '#data_colors' => array('AAAAAA'),
    '#chart_fill' => chart_fill('bg', '00000000'),
    '#shape_markers' => array(chart_shape_marker(0, 0, 'B', 0, $color = 'EEEEEE')),
    '#line_styles' => array(chart_line_style(2, 10, 0)),
  );
  $last_day = end($feed->results);
  $title = t('The most views on a single day was @max.  Yesterday there were @yesterday views.', array('@max' => $max_visits, '@yesterday' => $last_day['pageviews']));
  $output = '';
  $output .= chart_render($chart, array('title' => $title, 'style' => 'height:' . $chart['#size']['#height'] . 'px; width:100%'));
  $output .= '<p>' . l(t('@views views this month', array('@views' => number_format($feed->totals['pageviews']))), 'admin/reports/google-analytics') . '</p>';
  return $output;
}
