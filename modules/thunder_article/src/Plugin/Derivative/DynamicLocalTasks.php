<?php

namespace Drupal\thunder_article\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates content view local tasks.
 */
class DynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * Creates an DynamicLocalTasks object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(TranslationInterface $string_translation, protected readonly ModuleHandlerInterface $moduleHandler, protected readonly RouteProviderInterface $routeProvider, protected readonly ConfigFactoryInterface $configFactory) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): self {
    return new static(
      $container->get('string_translation'),
      $container->get('module_handler'),
      $container->get('router.route_provider'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    if ($this->moduleHandler->moduleExists('content_lock') && $this->routeProvider->getRoutesByNames(['view.locked_content.page_1'])) {
      $this->derivatives["thunder_article.content_lock"] = [
        'route_name' => "view.locked_content.page_1",
        'title' => $this->t('Locked content'),
        'parent_id' => "system.admin_content",
        'weight' => 2,
      ] + $base_plugin_definition;
    }

    if ($this->moduleHandler->moduleExists('access_unpublished') && $this->routeProvider->getRoutesByNames(['access_unpublished.access_token.list'])) {
      $this->derivatives["thunder_article.access_unpublished"] = [
        'route_name' => "access_unpublished.access_token.list",
        'title' => $this->t('Unpublished access'),
        'parent_id' => "system.admin_content",
        'weight' => 4,
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
