<?php

namespace Drupal\google_analytics_reports;

use GuzzleHttp\Exception\RequestException;
use Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed;

/**
 * GoogleAnalyticsReports service class.
 */
class GoogleAnalyticsReports {

  /**
   * Import Google Analytics fields to database using Metadata API.
   *
   * @see https://developers.google.com/analytics/devguides/reporting/metadata/v3/
   */
  public static function importFields() {
    if (!\defined('MAINTENANCE_MODE')) {
      try {
        $data = GoogleAnalyticsReportsApiFeed::service()->getMetadata();
      }
      catch (RequestException $e) {
        \Drupal::logger('google_analytics_reports')->error(
          'Failed to Google Analytics Column metadata definitions due to "%error".',
          ['%error' => $e->getMessage()]
        );

        return;
      }

      if ($data) {
        if (empty($data->getMetrics())) {
          \Drupal::logger('google_analytics_reports')->error(
            'Failed to Google Analytics Metrics/Dimensions metadata definitions. Received empty content.'
          );

          return;
        }

        // Remove old fields.
        if (
          \Drupal::database()
            ->schema()
            ->tableExists('google_analytics_reports_fields')
        ) {
          \Drupal::database()
            ->truncate('google_analytics_reports_fields')
            ->execute();
        }

        $google_analytics_reports_settings = \Drupal::config(
          'google_analytics_reports.settings'
        )->get();
        // Save current time as last executed time.
        $google_analytics_reports_settings['metadata_last_time'] = \Drupal::time()->getRequestTime();

        \Drupal::configFactory()
          ->getEditable('google_analytics_reports.settings')
          ->setData($google_analytics_reports_settings)
          ->save();

        if (!empty($data->getMetrics())) {
          $operations = [];

          foreach ($data->getMetrics() as $item) {
            $operations[] = [
              [GoogleAnalyticsReports::class, 'saveFields'],
              [['type' => 'METRIC'] + _to_array($item)],
            ];
          }

          foreach ($data->getDimensions() as $item) {
            $operations[] = [
              [GoogleAnalyticsReports::class, 'saveFields'],
              [['type' => 'DIMENSION'] + _to_array($item)],
            ];
          }
          $batch = [
            'operations' => $operations,
            'title' => t('Importing Google Analytics fields'),
            'finished' => [
              GoogleAnalyticsReports::class,
              'importFieldsFinished',
            ],
          ];
          batch_set($batch);
        }
      }
      else {
        \Drupal::messenger()->addMessage(
          t('There is a error during request to Google Analytics Metadata API'),
          'error'
        );
      }
    }
  }

  /**
   * Display messages after importing Google Analytics fields.
   *
   * @param bool $success
   *   Indicates whether the batch process was successful.
   * @param array $results
   *   Results information passed from the processing callback.
   */
  public static function importFieldsFinished(bool $success, array $results) {
    if ($success) {
      \Drupal::messenger()->addMessage(
        t('Imported @count Google Analytics fields.', [
          '@count' => \count($results),
        ])
      );
      // Hook_views_data() doesn't see the GA fields before cleaning cache.
      drupal_flush_all_caches();
    }
    else {
      \Drupal::messenger()->addMessage(
        t('An error has occurred during importing Google Analytics fields.'),
        'error'
      );
    }
  }

  /**
   * Batch processor.
   *
   * Saves Google Analytics fields from Metadata API to database.
   *
   * @param array $field
   *   Field definition.
   * @param array|\ArrayAccess $context
   *   Context.
   */
  public static function saveFields(array $field, &$context) {
    $field += [
      'gaid' => $field['api_name'],
      'data_type' => 'string',
      'column_group' => $field['category'],
      'calculation' => '',
    ];
    $fields = array_map(static function () {
      return '';
    }, array_flip([
      'gaid',
      'type',
      'data_type',
      'column_group',
      'ui_name',
      'description',
      'calculation',
    ]));
    $field = array_intersect_key($field, $fields);
    $field += $fields;
    $context['results'][] = $field['gaid'];

    // Allow other modules to alter Google Analytics fields before saving
    // in database.
    \Drupal::moduleHandler()->alter(
      'google_analytics_reports_field_import',
      $field
    );

    \Drupal::database()
      ->insert('google_analytics_reports_fields')
      ->fields($field)
      ->execute();
  }

}
