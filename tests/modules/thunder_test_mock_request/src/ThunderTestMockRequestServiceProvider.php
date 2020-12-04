<?php

namespace Drupal\thunder_test_mock_request;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Add mock handler and HTTP middleware.
 */
class ThunderTestMockRequestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    parent::alter($container);

    $container->getDefinition('http_handler_stack')
      ->addArgument(new Reference('thunder_test_mock_request.mock_handler'));

    $container
      ->register('thunder_test_mock_request.http_client.middleware', 'Drupal\thunder_test_mock_request\MockHttpClientMiddleware')
      ->addTag('http_client_middleware');
  }

}
