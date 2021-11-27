<?php

namespace Drupal\google_analytics_reports_api\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed;

/**
 * Represents the admin settings form for google_analytics_reports_api.
 */
class GoogleAnalyticsReportsApiAdminSettingsForm extends FormBase {
  /**
   * The config factory used by the config entity query.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The RequestStack service.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new Google Analytics Reports Api Admin Settings Form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The Date formatter.
   * @param Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    DateFormatterInterface $date_formatter,
    RequestStack $request_stack,
    EntityTypeManagerInterface $entity_type_manager,
    MessengerInterface $messenger
  ) {
    $this->configFactory = $config_factory;
    $this->dateFormatter = $date_formatter;
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * Save Google Analytics Reports API settings.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * Save Google Analytics Reports API settings.
   */
  public function adminSubmitSettings(
    array &$form,
    FormStateInterface $form_state
  ) {
    $image = $form_state->getValue(['json']);
    $fid = $image[0] ?? FALSE;
    $config = $this->config('google_analytics_reports_api.settings');
    $json = $config->get('json');

    if ((string) $json !== (string) $fid) {
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      $file->setPermanent();
      $file->save();
    }
    $config = $this->configFactory->getEditable('google_analytics_reports_api.settings');
    $config
      ->set('json', $fid)
      ->set('property', $form_state->getValue('property'))
      ->set('cache_length', $form_state->getValue('cache_length'))
      ->save();

    $this->messenger->addMessage($this->t('Settings have been saved successfully.'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $account = GoogleAnalyticsReportsApiFeed::service();
    $config = $this->config('google_analytics_reports_api.settings');

    $dev_console_url = Url::fromUri('https://console.developers.google.com');
    $dev_console_link = Link::fromTextAndUrl(
      $this->t('Google Developers Console'),
      $dev_console_url
    )->toRenderable();
    $dev_console_link['#attributes']['target'] = '_blank';

    $setup_help = $this->t(
      'To access data from Google Analytics you have to create a new project in Google Developers Console.'
    );
    $setup_help .= '<ol>';
    $setup_help .=
      ' <li>Add a credential and put json file here https://developers.google.com/analytics/devguides/reporting/data/v1/quickstart-client-libraries#step_2_add_service_account_to_the_google_analytics_4_property</li>';
    $setup_help .= '</ol>';

    $form['setup'] = [
      '#type' => 'details',
      '#title' => $this->t('Initial setup'),
      '#description' => $setup_help,
      '#open' => !$account || !$account->isAuthenticated(),
    ];

    $form['setup']['json'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Credential JSON'),
      '#upload_validators' => [
        'file_validate_extensions' => ['doc docx txt pdf json'],
      ],
      '#upload_location' => 'private://',
      '#description' => $this->t('Ensure private file system is setup'),
      '#default_value' => $config->get('json') !== NULL ? [$config->get('json')] : '',
    ];

    $form['setup']['property'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Property'),
      '#default_value' => $config->get('property'),
      '#size' => 75,
      '#description' => $this->t('A GA4 property to grab the data'),
      '#required' => TRUE,
    ];
    $form['setup']['settings_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
      '#submit' => ['::adminSubmitSettings'],
    ];

    if ($account && $account->isAuthenticated()) {
      $form['settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Settings'),
        '#open' => TRUE,
      ];

      // Default cache periods.
      $times = [];
      // 1-6 days.
      for ($days = 1; $days <= 6; ++$days) {
        $times[] = $days * 60 * 60 * 24;
      }
      // 1-4 weeks.
      for ($weeks = 1; $weeks <= 4; ++$weeks) {
        $times[] = $weeks * 60 * 60 * 24 * 7;
      }

      $options = array_map(
        [$this->dateFormatter, 'formatInterval'],
        array_combine($times, $times)
      );

      $form['settings']['cache_length'] = [
        '#type' => 'select',
        '#title' => $this->t('Query cache'),
        '#description' => $this->t(
          'The <a href="@link">Google Analytics Data API</a> restricts the number of queries made per day. This limits the creation of new reports on your site.  We recommend setting this cache option to at least three days.',
          [
            '@link' => Url::fromUri(
              'https://developers.google.com/analytics/devguides/reporting/data/v1/quotas',
              [
                'fragment' => 'core_reporting',
              ]
            )->toString(),
          ]
        ),
        '#options' => $options,
        '#default_value' => $config->get('cache_length'),
      ];

      $form['settings']['settings_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save settings'),
        '#submit' => ['::adminSubmitSettings'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_analytics_reports_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $image = $form_state->getValue(['json']);
    $fid = $image[0] ?? FALSE;
    $acc = FALSE;
    // Maybe user is removing fid @TODO, so no validating.
    if ($fid) {
      $acc = GoogleAnalyticsReportsApiFeed::service(
        ['json' => $fid] + $form_state->getValues()
      );
    }

    if (!$fid || !$acc || !$acc->isAuthenticated()) {
      $form_state->setErrorByName(
        'json',
        $this->t(
          'Credential is not valid. Check your configured property also!'
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_analytics_reports_api.settings'];
  }

}
