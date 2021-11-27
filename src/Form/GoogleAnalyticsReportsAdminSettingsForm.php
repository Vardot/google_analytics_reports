<?php

namespace Drupal\google_analytics_reports\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\google_analytics_reports\GoogleAnalyticsReports;
use Drupal\google_analytics_reports_api\Form\GoogleAnalyticsReportsApiAdminSettingsForm;
use Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Google Analytics Reports API Admin Settings form override.
 */
class GoogleAnalyticsReportsAdminSettingsForm extends GoogleAnalyticsReportsApiAdminSettingsForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $account = GoogleAnalyticsReportsApiFeed::service();

    if (
      $account instanceof GoogleAnalyticsReportsApiFeed
      && $account->isAuthenticated()
    ) {
      $google_analytics_reports_settings = $this->config(
        'google_analytics_reports.settings'
      )->get();
      $last_time = '';

      if (!empty($google_analytics_reports_settings['metadata_last_time'])) {
        $last_time = $google_analytics_reports_settings['metadata_last_time'];
      }
      $collapsed = !$last_time ? TRUE : FALSE;
      $form['fields'] = [
        '#type' => 'details',
        '#title' => $this->t('Import and update fields'),
        '#open' => $collapsed,
      ];

      if ($last_time) {
        $form['fields']['last_time'] = [
          '#type' => 'item',
          '#title' => $this->t('Google Analytics fields for Views integration'),
          '#description' => $this->t('Last import was @time.', [
            '@time' => $this->dateFormatter->format(
              $last_time,
              'custom',
              'd F Y H:i'
            ),
          ]),
        ];
      }
      $form['fields']['settings'] = [
        '#type' => 'submit',
        '#value' => $this->t('Import fields'),
        '#submit' => [[GoogleAnalyticsReports::class, 'importFields']],
      ];
    }

    return $form;
  }
}
