<?php

namespace Drupal\google_analytics_reports\Plugin\views\field;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Plugin\views\field\FieldPluginBase;

/**
 * Provides base field functionality for Google Analytics fields.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("google_analytics_standard")
 */
class GoogleAnalyticsStandard extends FieldPluginBase {
  use StringTranslationTrait;

}
