<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Query\QueryInterface;
use Drupal\thunder_gqls\Wrappers\SearchApiResponse;
use GraphQL\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The thunder base class for search api producers.
 */
abstract class ThunderSearchApiProducerBase extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  public const MAX_ITEMS = 100;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The response wrapper service.
   *
   * @var \Drupal\thunder_gqls\Wrappers\SearchApiResponse
   */
  private SearchApiResponse $responseWrapper;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->setEntityTypeManager($container->get('entity_type.manager'));
    $instance->setLanguageManager($container->get('language_manager'));
    $instance->setResponseWrapper($container->get('thunder_gqls.search_api_response_wrapper'));

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
   * Set the language manager service.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function setLanguageManager(LanguageManagerInterface $languageManager): void {
    $this->languageManager = $languageManager;
  }

  /**
   * Set the response wrapper service.
   *
   * @param \Drupal\thunder_gqls\Wrappers\SearchApiResponse $responseWrapper
   *   The response wrapper service.
   */
  public function setResponseWrapper(SearchApiResponse $responseWrapper): void {
    $this->responseWrapper = $responseWrapper;
  }

  /**
   * Build base search api query.
   *
   * @param int $limit
   *   Limit of the query.
   * @param int $offset
   *   Offset of the query.
   * @param string $index
   *   Id of the search api index.
   * @param array|null $sortBy
   *   List of sorts.
   * @param array|null $conditions
   *   List of conditions to filter the result.
   * @param string|null $search
   *   Query Search.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $cacheContext
   *   The caching context related to the current field.
   *
   * @return \Drupal\search_api\Query\QueryInterface|null
   *   The query interface.
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  protected function buildBaseQuery(
    int $limit,
    int $offset,
    string $index,
    ?array $sortBy,
    ?array $conditions,
    ?string $search,
    FieldContext $cacheContext,
  ): ?QueryInterface {

    // Make sure offset is zero or positive.
    $offset = max($offset, 0);

    // Make sure limit is positive and cap the max items.
    if ($limit <= 0) {
      $limit = 10;
    }
    if ($limit > static::MAX_ITEMS) {
      throw new UserError(
        sprintf('Exceeded maximum query limit: %s.', static::MAX_ITEMS)
      );
    }

    $searchIndex = Index::load($index);
    if (!$searchIndex) {
      return NULL;
    }

    $query = $searchIndex->query();

    foreach ($conditions as $condition) {
      $query->addCondition($condition['field'], $condition['value'], $condition['operator']);
    }

    foreach ($sortBy as $sort) {
      $direction = $sort['direction'] ?? QueryInterface::SORT_ASC;
      $query->sort($sort['field'], $direction);
    }

    if (!empty($search)) {
      $query->keys($search);
    }

    $query->range($offset, $limit);
    $cacheContext->addCacheableDependency($searchIndex);
    foreach ($searchIndex->getDatasources() as $datasource) {
      $storage = $this->entityTypeManager->getStorage($datasource->getEntityTypeId());
      $entityType = $storage->getEntityType();

      $cacheContext->addCacheTags($entityType->getListCacheTags());
      $cacheContext->addCacheContexts($entityType->getListCacheContexts());
    }

    return $query;
  }

  /**
   * The search api response.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search api query.
   *
   * @return \Drupal\thunder_gqls\Wrappers\SearchApiResponse
   *   The search api response.
   */
  protected function searchApiResponse(QueryInterface $query): SearchApiResponse {
    return $this->responseWrapper->setQuery($query);
  }

}
