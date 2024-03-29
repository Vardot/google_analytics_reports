<?php

/**
 * @file
 * Views hook implementations for Google Analytics Reports module.
 */

use Drupal\Component\Render\FormattableMarkup;

/**
 * Implements hook_views_data().
 */
function google_analytics_reports_views_data() {
  $data = [
    'google_analytics' => [
      'table' => [
        'group' => t('Google Analytics'),
        'base' => [
          'title' => t('Google Analytics'),
          'query_id' => 'google_analytics_query',
          'help' => t('Views Google Analytics query builder'),
        ],
      ],
      'start_date' => [
        'title' => t('Start date of report'),
        'help' => t('Start date of report'),
        'argument' => [
          'id' => 'google_analytics_argument',
        ],
        'filter' => [
          'id' => 'google_analytics_date',
        ],
        'sort' => [
          'id' => 'date',
        ],
      ],
      'end_date' => [
        'title' => t('End date of report'),
        'help' => t('End date of report'),
        'argument' => [
          'id' => 'google_analytics_argument',
        ],
        'filter' => [
          'id' => 'google_analytics_date',
        ],
        'sort' => [
          'id' => 'date',
        ],
      ],
      'profile_id' => [
        'title' => t('Profile ID'),
        'help' => t('Profile ID'),
        'argument' => [
          'id' => 'google_analytics_argument',
        ],
        'filter' => [
          'id' => 'google_analytics_string',
        ],
      ],
    ],
  ];

  $fields = google_analytics_reports_get_fields();

  foreach ($fields as $field_name => $field) {
    // Description of filed from Google Analytics.
    $field->description =
      $field->description .
      '<br />' .
      t('Type: @type.', ['@type' => $field->type]);

    if (isset($field->calculation) && $field->calculation) {
      $field->description .=
        '<br />' .
        t('Calculation: <code>@formula</code>.', [
          '@formula' => $field->calculation,
        ]);
    }
    $field->description .=
      '<br />' . t('API name: <code>@ga</code>.', ['@ga' => $field_name]);

    // Provide default handler.
    $field_handler = 'standard';
    $float = FALSE;

    if (in_array($field->data_type, ['date', 'time'], TRUE)) {
      $field_handler = 'date';
    }
    elseif (
      in_array(
        $field->data_type,
        ['integer', 'float', 'percent', 'currency'],
        TRUE
      )
    ) {
      $field_handler = 'numeric';
      $float = TRUE;
    }
    $data['google_analytics'][$field_name] = [
      'title' => $field->ui_name,
      // Use this because of escaping markup in Views UI.
      'help' => new FormattableMarkup($field->description, []),
      'group' => $field->column_group,
      'field' => [
        'id' => $field_handler,
        'click sortable' => TRUE,
        'float' => $float,
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'google_analytics_argument',
      ],
      'filter' => [
        'id' => $field->type === 'metric'
        ? 'google_analytics_numeric'
        : 'google_analytics_string',
      ],
    ];
  }

  return $data;
}
