<?php

namespace Drupal\google_analytics_reports\Plugin\views\argument;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

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
