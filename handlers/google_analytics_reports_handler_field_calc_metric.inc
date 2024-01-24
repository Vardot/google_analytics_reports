<?php

/**
 * @file
 * Definition of google_analytics_reports_handler_field.
 */

/**
 * Provides field functionality for Google Analytics calculated metric fields.
 *
 * @ingroup views_field_handlers
 */
class google_analytics_reports_handler_field_calc_metric extends views_handler_field_numeric {

  /**
   * Stores whether this is a calculated metric field or not.
   *
   * @var bool
   */
  protected $is_calc_metric = FALSE;

  /**
   * {@inheritdoc}
   */
  function init(&$view, &$options) {
    parent::init($view, $options);

    $this->is_calc_metric = google_analytics_reports_is_calc_metric($this->real_field);
  }

  /**
   * {@inheritdoc}
   */
  function query() {
    if ($this->is_calc_metric && !empty($this->options['calc_metric_name'])) {
      $this->real_field = google_analytics_reports_calc_metric_name($this->real_field, $this->options['calc_metric_name']);
    }

    parent::query();
  }

  /**
   * {@inheritdoc}
   */
  function option_definition() {
    $options = parent::option_definition();

    if ($this->is_calc_metric) {
      $options['calc_metric_name'] = array('default' => '');
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  function options_form(&$form, &$form_state) {
    if ($this->is_calc_metric) {
      $form['calc_metric_name'] = array(
        '#type' => 'textfield',
        '#title' => t('Calculated metric name'),
        '#default_value' => isset($this->options['calc_metric_name']) ? $this->options['calc_metric_name'] : '',
        '#required' => TRUE,
      );
    }

    parent::options_form($form, $form_state);
  }

}
