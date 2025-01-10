<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\thunder_gqls\Wrappers\EntityListResponse;
use GraphQL\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The thunder base class for entity list producers.
 */
abstract class ThunderEntityListProducerBase extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  public const MAX_ITEMS = 100;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The response wrapper service.
   *
   * @var \Drupal\thunder_gqls\Wrappers\EntityListResponse
   */
  protected EntityListResponse $responseWrapper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->setResponseWrapper($container->get('thunder_gqls.entity_list_response_wrapper'));
    $instance->setEntityTypeManager($container->get('entity_type.manager'));
    $instance->setCurrentUser($container->get('current_user'));

    return $instance;
  }

  /**
   * Set the entity type manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entityTypeManager): void {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Set the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function setCurrentUser(AccountInterface $currentUser): void {
    $this->currentUser = $currentUser;
  }

  /**
   * Set the response wrapper service.
   *
   * @param \Drupal\thunder_gqls\Wrappers\EntityListResponse $responseWrapper
   *   The response wrapper service.
   */
  public function setResponseWrapper(EntityListResponse $responseWrapper): void {
    $this->responseWrapper = $responseWrapper;
  }

  /**
   * Build base entity query which may be reused for count query as well.
   *
   * @param string $type
   *   Entity type.
   * @param string[] $bundles
   *   List of bundles to be filtered.
   * @param int $offset
   *   Query only entities owned by current user.
   * @param int $limit
   *   Maximum number of queried entities.
   * @param array $conditions
   *   List of conditions to filter the entities.
   * @param string[] $languages
   *   Languages for queried entities.
   * @param array $sortBy
   *   List of sorts.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $cacheContext
   *   The caching context related to the current field.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query interface.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function query(
    string $type,
    array $bundles,
    int $offset,
    int $limit,
    array $conditions,
    array $languages,
    array $sortBy,
    FieldContext $cacheContext,
  ): QueryInterface {
    if ($limit > static::MAX_ITEMS) {
      throw new UserError(
        sprintf('Exceeded maximum query limit: %s.', static::MAX_ITEMS)
      );
    }

    $entity_type = $this->entityTypeManager->getStorage($type);
    $query = $entity_type->getQuery();

    // Ensure that access checking is performed on the query.
    $query->currentRevision()->accessCheck(TRUE);

    // Ensure that only published entities are shown.
    if ($publishedCondition = $this->createPublishedCondition($type, $conditions)) {
      $conditions[] = $publishedCondition;
    }

    // Filter entities only of given bundles, if desired.
    if ($bundles) {
      $bundle_key = $entity_type->getEntityType()->getKey('bundle');
      if (!$bundle_key) {
        throw new UserError('No bundles defined for given entity type.');
      }
      $query->condition($bundle_key, $bundles, 'IN');
    }

    // Filter entities by given languages, if desired.
    if ($languages) {
      $query->condition('langcode', $languages, 'IN');
    }

    // Filter by given conditions.
    foreach ($conditions as $condition) {
      $operation = $condition['operator'] ?? NULL;
      $query->condition($condition['field'], $condition['value'], $operation);
    }

    if (!empty($sortBy)) {
      foreach ($sortBy as $sort) {
        if (!empty($sort['field'])) {
          if (!empty($sort['direction']) && strtolower(
              $sort['direction']
            ) === 'desc') {
            $direction = 'DESC';
          }
          else {
            $direction = 'ASC';
          }
          $query->sort($sort['field'], $direction);
        }
      }
    }

    $query->range($offset, $limit);

    $storage = $this->entityTypeManager->getStorage($type);
    $entityType = $storage->getEntityType();

    $cacheContext->addCacheTags($entityType->getListCacheTags());
    $cacheContext->addCacheContexts($entityType->getListCacheContexts());
    return $query;
  }

  /**
   * Creates a published entity query condition, if it does not exist.
   *
   * @param string $type
   *   The entity type.
   * @param array $conditions
   *   The existing conditions.
   *
   * @return array|bool
   *   The published entity query condition for the given entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function createPublishedCondition(string $type, array $conditions) {
    $definition = $this->entityTypeManager->getDefinition($type);
    if (!$definition->hasKey('published')) {
      return FALSE;
    }

    $publishedKey = $definition->getKey('published');
    foreach ($conditions as $condition) {
      if (isset($condition['field']) && $condition['field'] === $publishedKey) {
        return FALSE;
      }
    }

    return [
      'field' => $publishedKey,
      'value' => '1',
    ];
  }

  /**
   * The entity list response.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query.
   *
   * @return \Drupal\thunder_gqls\Wrappers\EntityListResponse
   *   The entity list response.
   */
  protected function entityListResponse(QueryInterface $query): EntityListResponse {
    return $this->responseWrapper->setQuery($query);
  }

}
