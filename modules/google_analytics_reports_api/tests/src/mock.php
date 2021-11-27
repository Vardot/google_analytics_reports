<?php

/**
 * @file
 */

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\DependencyInjection\ContainerBuilder;

if (!function_exists('t')) {
  require_once './web/core/includes/bootstrap.inc';
}

function mock_drupal_services($item, $config_map = []) {

  foreach ([
    ['Drupal\\Core\\StreamWrapper\\StreamWrapperManager', 'stream_wrapper_manager', function ($item, &$themock) {
      $fake = $item->getMockBuilder('Drupal\Core\StreamWrapper\PrivateStream')
        ->disableOriginalConstructor()
        ->getMock();
      $file = realpath(__DIR__ . '/credential.json');
      $fake->expects($item->any())
        ->method('realpath')
        ->willReturn($file);

      $themock->expects($item->any())
        ->method('getViaUri')
        ->willReturn($fake);
    },
],
    ['Drupal\\Core\\Logger\\LoggerChannelFactory', 'logger.factory', function ($item, &$themock) {
      $themock->method('get')
        ->will($item->returnValue(new LoggerChannel('test')));
    },
],
    ['Drupal\\Core\\Messenger\\Messenger', 'messenger'],
    ['Drupal\\Core\\Extension\\ModuleHandler', 'module_handler'],
    ['Drupal\\Core\\Cache\\DatabaseBackend', 'cache.backend.database', function ($item, &$themock) {
    },
],
    ['Drupal\\Core\\Cache\\CacheFactory', 'cache_factory', function ($item, &$themock) {
      $file = $item->getMockBuilder('Drupal\Core\Cache\NullBackend')
        ->disableOriginalConstructor()
        ->getMock();

      $themock->expects($item->any())
        ->method('get')
        ->will($item->returnValueMap([
          ['default', $file],
          ['google_analytics_reports_api', $file],
        ]));
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
    $methods = get_class_methods($class);
    $sv = $item
      ->getMockBuilder($class)
      ->disableOriginalConstructor()
      ->setMethods($methods)
      ->getMock($key);
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
