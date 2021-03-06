<?php

namespace Drupal\google_analytics_reports\Plugin\views\filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Basic textfield filter to handle string filtering commands.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("google_analytics_string")
 */
class GoogleAnalyticsString extends GoogleAnalyticsBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }

    $options = $this->operatorOptions('short');
    $output = '';

    if (!empty($options[$this->operator])) {
      $output = Html::escape($options[$this->operator]);
    }

    if (\in_array($this->operator, $this->operatorValues(1), TRUE)) {
      $output .= ' ' . Html::escape($this->value);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['expose']['contains']['required'] = ['default' => FALSE];

    return $options;
  }

  /**
   * Operation contains.
   *
   * @param string $field
   *   Field name.
   */
  public function opContains($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value, '=@');
  }

  /**
   * Operation Equality.
   *
   * @param string $field
   *   Field name.
   */
  public function opEqual($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value, '==');
  }

  /**
   * Provides list of operators.
   *
   * This kind of construct makes it relatively easy for a child class
   * to add or remove functionality by overriding this function and
   * adding/removing items from this array.
   *
   * {@inheritdoc}
   */
  public function operators() {
    return [
      '=' => [
        'title' => $this->t('Is equal to'),
        'short' => $this->t('='),
        'method' => 'opEqual',
        'values' => 1,
      ],
      '!=' => [
        'title' => $this->t('Is not equal to'),
        'short' => $this->t('!='),
        'method' => 'opInequal',
        'values' => 1,
      ],
      'contains' => [
        'title' => $this->t('Contains'),
        'short' => $this->t('contains'),
        'method' => 'opContains',
        'values' => 1,
      ],
      'not' => [
        'title' => $this->t('Does not contain'),
        'short' => $this->t('!has'),
        'method' => 'opNot',
        'values' => 1,
      ],
      'regular_expression' => [
        'title' => $this->t('Contains a match for the regular expression'),
        'short' => $this->t('regex'),
        'method' => 'opRegex',
        'values' => 1,
      ],
      'not_regular_expression' => [
        'title' => $this->t('Does not match regular expression'),
        'short' => $this->t('!regex'),
        'method' => 'opNotRegex',
        'values' => 1,
      ],
    ];
  }

  /**
   * Operator values.
   *
   * @param int $values
   *   Value.
   *
   * @return array
   *   Operator keys.
   */
  public function operatorValues($values = 1) {
    $options = [];

    foreach ($this->operators() as $id => $info) {
      if (isset($info['values']) && $info['values'] === $values) {
        $options[] = $id;
      }
    }

    return $options;
  }

  /**
   * Operation non-equality.
   *
   * @param string $field
   *   Field name.
   */
  public function opInequal($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value, '!=');
  }

  /**
   * Operation not.
   *
   * @param string $field
   *   Field name.
   */
  public function opNot($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value, '!@');
  }

  /**
   * Operation regex not match.
   *
   * @param string $field
   *   Field name.
   */
  public function opNotRegex($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value, '!~');
  }

  /**
   * Operation regex match.
   *
   * @param string $field
   *   Field name.
   */
  public function opRegex($field) {
    $this->query->addWhere($this->options['group'], $field, $this->value, '=~');
  }

  /**
   * Provide a simple textfield for equality.
   *
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    $values = $form_state->getValues();

    // We have to make some choices when creating this as an exposed
    // filter form. For example, if the operator is locked and thus
    // not rendered, we can't render dependencies; instead we only
    // render the form items we need.
    $which = 'all';

    if (!empty($form['operator'])) {
      $source =
        $form['operator']['#type'] === 'radios'
          ? 'radio:options[operator]'
          : 'edit-options-operator';
    }

    if (!empty($values['exposed'])) {
      $identifier = $this->options['expose']['identifier'];

      if (
        empty($this->options['expose']['use_operator'])
        || empty($this->options['expose']['operator_id'])
      ) {
        // Exposed and locked.
        $which = \in_array($this->operator, $this->operatorValues(1), TRUE)
          ? 'value'
          : 'none';
      }
      else {
        $source =
          'edit-' . Html::getUniqueId($this->options['expose']['operator_id']);
      }
    }

    if ($which === 'all' || $which === 'value') {
      $form['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Value'),
        '#size' => 30,
        '#default_value' => $this->value,
      ];

      if (!empty($values['exposed']) && !isset($values['input'][$identifier])) {
        $values['input'][$identifier] = $this->value;
      }

      if ($which === 'all') {
        $form['value'] += [
          '#dependency' => [$source => $this->operatorValues(1)],
        ];
      }
    }

    if (!isset($form['value'])) {
      // Ensure there is something in the 'value'.
      $form['value'] = [
        '#type' => 'value',
        '#value' => NULL,
      ];
    }
  }

}
