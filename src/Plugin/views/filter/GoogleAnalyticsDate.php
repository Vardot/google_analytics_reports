<?php
/**
 * @file
 * Contains \Drupal\google_analytics_reports\Plugin\views\filter\GoogleAnalyticsDate.
 */

namespace Drupal\google_analytics_reports\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Date;

/**
 * A handler to provide filters for Google Analytics dates.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("google_analytics_date")
 */
class GoogleAnalyticsDate extends Date {

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = [
      '=' => [
        'title' => $this->t('Is equal to'),
        'method' => 'opSimple',
        'short' => $this->t('='),
        'values' => 1,
      ],
    ];
    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    $value = intval(strtotime($this->value['value'], 0));
    if (!empty($this->value['type']) && $this->value['type'] == 'offset') {
      $value = REQUEST_TIME + $value;
    }
    $this->query->addWhere($this->options['group'], $field, $value, $this->operator);
  }

}
