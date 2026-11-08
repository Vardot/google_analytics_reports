<?php

/**
 * @file
 */

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\DependencyInjection\ContainerBuilder;

if (!function_exists('t')) {
  require_once './web/core/includes/bootstrap.inc';
}

/**
 * Creates a mock in the test case's own scope.
 *
 * TestCase::createMock() is protected, so it is called through a closure bound
 * to the test case rather than from this file's global scope.
 */
function mock_drupal_create_mock($item, string $class) {
  return (function (string $class) {
    return $this->createMock($class);
  })->call($item, $class);
}

function mock_drupal_services($item, $config_map = []) {

  foreach ([
    ['Drupal\\Core\\StreamWrapper\\StreamWrapperManager', 'stream_wrapper_manager', function ($item, &$themock) {
      $fake = mock_drupal_create_mock($item, 'Drupal\Core\StreamWrapper\PrivateStream');
      $file = realpath(__DIR__ . '/credential.json');
      $fake->method('realpath')
        ->willReturn($file);

      $themock->method('getViaUri')
        ->willReturn($fake);
    },
],
    ['Drupal\\Core\\Logger\\LoggerChannelFactory', 'logger.factory', function ($item, &$themock) {
      $themock->method('get')
        ->willReturn(new LoggerChannel('test'));
    },
],
    ['Drupal\\Core\\Messenger\\Messenger', 'messenger'],
    ['Drupal\\Core\\Extension\\ModuleHandler', 'module_handler'],
    ['Drupal\\Core\\Cache\\DatabaseBackend', 'cache.backend.database', function ($item, &$themock) {
    },
],
    ['Drupal\\Core\\Cache\\CacheFactory', 'cache_factory', function ($item, &$themock) {
      $file = mock_drupal_create_mock($item, 'Drupal\Core\Cache\NullBackend');

      $themock->method('get')
        ->willReturnMap([
          ['default', $file],
          ['google_analytics_reports_api', $file],
        ]);
    },
],
    ['Drupal\\Component\\Datetime\\Time', 'datetime.time'],
    ['Symfony\\Component\\HttpFoundation\\RequestStack', 'request_stack'],
    ['Drupal\\Core\\Entity\\EntityTypeManager', 'entity_type.manager', function ($item, &$themock) {
    },
    ],
    [$item->getConfigFactoryStub($config_map), 'config.factory'],
  ] as $it) {
    $it += [FALSE, FALSE, function ($it, $sv) {
    },
];
    list ($class, $key, $callback) = $it;
    mock_drupal_service($item, $class, $key, $callback);
  }
}

function mock_drupal_service($item, $class, $key, $callback) {
  static $container;
  if (!is_object($class)) {
    $sv = mock_drupal_create_mock($item, $class);
  }
  else {
    $sv = $class;
  }
  $callback($item, $sv);
  if (!isset($container)) {
    $container = new ContainerBuilder();
  }
  $container
    ->set($key, $sv);
  \Drupal::setContainer($container);
}
