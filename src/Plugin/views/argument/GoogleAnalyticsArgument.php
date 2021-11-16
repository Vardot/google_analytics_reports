<?php

namespace Drupal\google_analytics_reports\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Provides base argument functionality for Google Analytics fields.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("google_analytics_argument")
 */
class GoogleAnalyticsArgument extends ArgumentPluginBase {
  use StringTranslationTrait;

  /**
   * Operator.
   *
   * @var object
   */
  public $operator;

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
  public function query($group_by = FALSE) {
    $this->operator = '==';
    $this->query->addWhere(
      1,
      $this->realField,
      $this->argument,
      $this->operator
    );
  }

}
