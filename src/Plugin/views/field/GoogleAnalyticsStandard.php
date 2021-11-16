<?php

namespace Drupal\google_analytics_reports\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Provides base field functionality for Google Analytics fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("google_analytics_standard")
 */
class GoogleAnalyticsStandard extends FieldPluginBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    return parent::defineOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function init(
    ViewExecutable $view,
    DisplayPluginBase $display,
    ?array &$options = NULL
  ) {
    parent::init($view, $display, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();
  }

}
