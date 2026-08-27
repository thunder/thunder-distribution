<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Supplies row operations for the AI prompt entity type.
 *
 * The admin listing itself is the "AI Prompts" view (views.view.ai_prompts);
 * this handler only backs its "Operations" bulk-form/entity_operations field
 * and the row-level test-prompt link.
 */
final class AIPromptListBuilder extends EntityListBuilder {

  public function __construct(
    EntityTypeInterface $entity_type,
    EntityTypeManagerInterface $entityTypeManager,
    protected RouteProviderInterface $routeProvider,
  ) {
    parent::__construct($entity_type, $entityTypeManager->getStorage($entity_type->id()));
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = $container->get('router.route_provider');
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static(
      $entity_type,
      $entity_type_manager,
      $route_provider,
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity, ?CacheableMetadata $cacheability = NULL): array {
    $operations = parent::getDefaultOperations($entity, $cacheability);

    if ($entity->access('update') && $this->isTestRouteAvailable()) {
      $operations['test'] = [
        'title' => $this->t('Test'),
        'weight' => 15,
        'url' => Url::fromRoute('entity.ai_prompt_content.test_form', ['ai_prompt_content' => $entity->id()]),
      ];
    }

    return $operations;
  }

  /**
   * Checks whether the prompt test route exists in the active routing table.
   */
  private function isTestRouteAvailable(): bool {
    try {
      $this->routeProvider->getRouteByName('entity.ai_prompt_content.test_form');
      return TRUE;
    }
    catch (RouteNotFoundException) {
      return FALSE;
    }
  }

}
