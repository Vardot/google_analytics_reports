<?php

namespace Drupal\Tests\google_analytics_reports_api\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed;

/**
 * @coversDefaultClass \Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed
 * @group google_analytics_reports
 */
class GoogleAnalyticsReportsApiFeedTest extends UnitTestCase {
  /**
   * The API class.
   *
   * @var \Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed
   */
  protected $class;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once dirname(__DIR__) . '/mock.php';

    $config_map = [
      'google_analytics_reports_api.settings' => $this->providerTestObj()[1][1],
    ];
    mock_drupal_services($this, $config_map);
  }

  /**
   * Asserts the main API object. Should return proper object based on the given config.
   *
   * @dataProvider providerTestObj
   * @covers ::service
   *
   * @param $expected_result
   * @param $settings
   */
  public function testObj($expected_result, $settings) {
    $config_map = [
      'google_analytics_reports_api.settings' => $settings,
    ];
    mock_drupal_services($this, $config_map);
    $result = GoogleAnalyticsReportsApiFeed::service();
    $this->assertSame($expected_result, is_object($result));
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerTestObj() {
    return [
      [FALSE, []],
      [TRUE, [
        'json' => dirname(__DIR__) . '/credential.json',
        'property' => '222',
      ],
],
    ];
  }

  /**
   * Asserts the methods of main API object. Should return proper results based the official Google Analytics GA4's APIs.
   * https://developers.google.com/analytics/devguides/reporting/data/v1.
   *
   * @dataProvider providerTestObjMethod
   * @covers ::__call
   *
   * @param $expected_result
   * @param $method
   */
  public function testObjMethod($expected_result, $method) {
    try {
      $result = GoogleAnalyticsReportsApiFeed::service()->{$method}();
      $isAvailable = TRUE;
    }
    catch (\Throwable $e) {
      $isAvailable = strpos($e->getMessage(), 'a valid callback') === FALSE;
    }
    $this->assertSame($isAvailable, $expected_result);
  }

  /**
   * Provides data and expected results for the test method.
   *
   * @return array
   *   Data and expected results.
   */
  public function providerTestObjMethod() {
    return [
      [FALSE, 'invalidMethod'],
      [TRUE, 'runReport'],
      [TRUE, 'batchRunReports'],
      [TRUE, 'runPivotReport'],
      [TRUE, 'batchRunPivotReports'],
      [TRUE, 'getMetadata'],
      [TRUE, 'runRealtimeReport'],
      [TRUE, 'isAuthenticated'],
    ];
  }

}
