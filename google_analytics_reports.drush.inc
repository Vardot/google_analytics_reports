<?php

/**
 * @file
 * Drush integration for Google Analytics Reports module.
 */

/**
 * Implements hook_drush_command().
 */
function google_analytics_reports_drush_command() {
  $commands = [];

  $commands['google-analytics-reports-fields'] = [
    'aliases' => ['garf'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'description' => dt('Import Google Analytics fields.'),
    'examples' => [
      'drush google-analytics-reports-fields' => dt(
        'Import Google Analytics fields for Google Analytics Reports module.'
      ),
    ],
    'callback' => 'drush_google_analytics_reports_fields',
  ];

  return $commands;
}

/**
 * Implements drush_MODULE_post_pm_enable().
 */
function drush_google_analytics_reports_post_pm_enable() {
  $modules = func_get_args();

  if (in_array('google_analytics_reports', $modules, TRUE)) {
    drush_google_analytics_reports_fields();
  }
}

/**
 * Imports Google Analytics Fields.
 */
function drush_google_analytics_reports_fields() {
  \Drupal::logger('google_analytics_reports')->error(
    'Drush support is not available now'
  );
}
