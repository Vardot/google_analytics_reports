<?php
/**
 * @file
 * Theme preprocess functions for google analytics.
 */

function template_preprocess_google_analytics_reports_summary(&$vars) {
  drupal_add_css(drupal_get_path('module', 'google_analytics_reports') . '/google_analytics_reports.css', 'module', 'all', FALSE);

  $vars['visit_chart'] = $vars['summary']['visit_chart'];
  $vars = array_merge($vars, $vars['summary']['usage']);

  if ($vars['visits'] <= 0) {
    $vars['pages_per_visit'] = '0.00';
    $vars['newVisits'] = '0.00%';
    $vars['timeOnSite'] = '0:00';
  }
  else {
    $vars['pages_per_visit'] = number_format(round($vars['pageviews'] / $vars['visits'], 2), 2);
    $vars['newVisits'] = number_format(round(($vars['newVisits'] / $vars['visits'])*100, 2), 2) . '%';
    $vars['timeOnSite'] = format_interval($vars['timeOnSite'] / $vars['visits']);
  }

  if ($vars['entrances'] <= 0) {
    $vars['bounces'] = '0.00%';
  }
  else {
    $vars['bounces'] = number_format(round(($vars['bounces'] / $vars['entrances'])*100, 2), 2) . '%';
  }

  $vars['entrances'] = number_format($vars['entrances']);
  $vars['pageviews'] = number_format($vars['pageviews']);

  $pages = array();
  foreach ($vars['summary']['pages'] as $page) {
    if ($page['pagePath'] == '/index.html') {
      $page['pagePath'] = '/';
    }
    $pages[] = l($page['pageTitle'], "http://$page[hostname]$page[pagePath]", array('external' => TRUE, 'html' => TRUE)) . ' - ' . format_plural($page['pageviews'], '1 View', '@views Views', array('@views' => number_format($page['pageviews'])));
  }
  $vars['pages'] = theme('item_list', $pages, NULL, 'ol');

  $referrals = array();
  foreach ($vars['summary']['referrals'] as $referral) {
    $referrals[] = '<strong>' . $referral['source'] . '</strong> - ' . format_plural($referral['visits'], '1 Visit', '@visits Visits', array('@visits' => number_format($referral['visits'])));
  }
  $vars['referrals'] = theme('item_list', $referrals, NULL, 'ol');

  $searches = array();
  foreach ($vars['summary']['searches'] as $search) {
    $searches[] = '<strong>' . $search['keyword'] . '</strong> - ' . format_plural($search['visits'], '1 Visit', '@visits Visits', array('@visits' => number_format($search['visits'])));
  }
  $vars['searches'] = theme('item_list', $searches, NULL, 'ol');
}
