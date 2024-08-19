<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
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
   * The class resolver service.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  private ClassResolverInterface $classResolver;

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
    $instance->setClassResolver($container->get('class_resolver'));

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
   * Set the class resolver service.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver service.
   */
  public function setClassResolver(ClassResolverInterface $classResolver): void {
    $this->classResolver = $classResolver;
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

    $defaultConditions = [
      'status' => TRUE,
      'search_api_language' => $this->languageManager->getCurrentLanguage()->getId(),
    ];

    $conditions = array_merge($defaultConditions, $conditions);

    $query = $searchIndex->query();

    foreach ($conditions as $field => $value) {
      $query->addCondition($field, $value);
    }

    if (!empty($search)) {
      $query->keys($search);
    }

    $query->range($offset, $limit);
    $query->sort('search_api_relevance', QueryInterface::SORT_DESC);

    $cacheContext->addCacheTags($searchIndex->getCacheTags());
    $cacheContext->addCacheContexts($searchIndex->getCacheContexts());

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
    return $this->classResolver
      ->getInstanceFromDefinition(SearchApiResponse::class)
      ->setQuery($query);
  }

}
