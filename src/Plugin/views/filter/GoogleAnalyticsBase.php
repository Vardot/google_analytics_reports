<?php

namespace Drupal\google_analytics_reports\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Provides base filter functionality for Google Analytics fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("google_analytics_base")
 */
class GoogleAnalyticsBase extends FilterPluginBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $alwaysMultiple = TRUE;

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
  public function operatorOptions($which = 'title') {
    $options = [];

    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * Provide list of operators.
   */
  public function operators() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $info = $this->operators();

    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($this->realField);
    }
  }

}
