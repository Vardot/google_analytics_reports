<?php

/**
 * @file
 * Definition of google_analytics_reports_handler_field.
 */

/**
 * Provides base field functionality for Google Analytics fields.
 *
 * @ingroup views_field_handlers
 */
class google_analytics_reports_handler_field extends views_handler_field {

  /**
   * @var bool
   * Stores whether this is a custom field or not.
   */
  var $is_custom = NULL;

  /**
   * {@inheritdoc}
   */
  function init(&$view, &$options) {
    parent::init($view, $options);

    $this->is_custom = google_analytics_reports_is_custom($this->real_field);
  }

  /**
   * {@inheritdoc}
   */
  function query() {
    if ($this->is_custom) {
      if ($this->real_field == 'metricXX') {
        $field_label = google_analytics_reports_custom_metric_id($this->options['custom_field_label']);
      }
      elseif ($this->real_field == 'dimensionXX') {
        $field_label = google_analytics_reports_custom_dimension_id($this->options['custom_field_label']);
      }

      if (!empty($field_label)) {
        // The dimensions id has a ga: prefix, which is actually added later by
        // the google_analytics_reports_api query, so we need to remove it here.
        $this->real_field = $field_label;
      }
    }

    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  function option_definition() {
    $options = parent::option_definition();

    if ($this->is_custom) {
      $options['custom_field_label'] = array('default' => '');
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  function options_form(&$form, &$form_state) {
    if ($this->is_custom) {
      if ($this->real_field == 'metricXX') {
        $options = googleanalytics_reports_custom_metric_options();
        $label = t('Custom metric');
      }
      elseif ($this->real_field == 'dimensionXX') {
        $options = googleanalytics_reports_custom_dimension_options();
        $label = t('Custom dimension');
      }

      if (!empty($options)) {
        $form['custom_field_label'] = [
          '#type' => 'select',
          '#title' => $label,
          '#default_value' => isset($this->options['custom_field_label']) ? $this->options['custom_field_label'] : '',
          '#options' => $options,
          '#required' => TRUE,
        ];
      }
    }

    parent::options_form($form, $form_state);
  }

}
