<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Query\QueryInterface;
use GraphQL\Error\UserError;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The thunder base class for search api producers.
 */
class ThunderSearchApiProducerBase extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  public const MAX_ITEMS = 100;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
    );
  }

  /**
   * EntityLoad constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  public function __construct(
    array $configuration,
    string $pluginId,
    array $pluginDefinition,
    EntityTypeManager $entityTypeManager,
    LanguageManagerInterface $languageManager,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
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

}
