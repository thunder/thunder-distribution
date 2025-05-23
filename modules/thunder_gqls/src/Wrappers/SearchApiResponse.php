<?php

namespace Drupal\thunder_gqls\Wrappers;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\facets\Entity\Facet;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\thunder_gqls\GraphQL\Buffers\SearchApiResultBuffer;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SearchApi Result graphql wrapper.
 *
 * @package Drupal\thunder_gqls
 */
class SearchApiResponse implements SearchApiResponseInterface, ContainerInjectionInterface {

  /**
   * The Search Api Query.
   *
   * @var \Drupal\search_api\Query\QueryInterface
   */
  protected QueryInterface $query;

  /**
   * The SearchApi Result.
   *
   * @var \Drupal\search_api\Query\ResultSetInterface|null
   */
  protected ?ResultSetInterface $result = NULL;

  /**
   * Array of Facets.
   *
   * @var array
   */
  protected array $facets;

  /**
   * Array of Facet mapping.
   *
   * @var array
   */
  protected array $facetMapping;

  /**
   * The bundle for fetching facet field information.
   *
   * @var string
   */
  protected string $bundle;

  /**
   * SearchApiResponse Constructor.
   *
   * @param \Drupal\thunder_gqls\GraphQL\Buffers\SearchApiResultBuffer $buffer
   *   The search api result buffer.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity type manager.
   */
  public function __construct(protected SearchApiResultBuffer $buffer, protected EntityFieldManagerInterface $entityFieldManager) {
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('thunder_gqls.buffer.search_api_result'),
      $container->get('entity_field.manager'),
    );
  }

  /**
   * Set query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query.
   */
  public function setQuery(QueryInterface $query): SearchApiResponse {
    $this->query = $query;
    return $this;
  }

  /**
   * Set Facet mapping.
   *
   * @param array $facetMapping
   *   The facet mapping.
   */
  public function setFacetMapping(array $facetMapping): SearchApiResponse {
    $this->facetMapping = $facetMapping;
    return $this;
  }

  /**
   * Set bundle.
   *
   * @param string $bundle
   *   The bundle.
   */
  public function setBundle(string $bundle): SearchApiResponse {
    $this->bundle = $bundle;
    return $this;
  }

  /**
   * Set facets.
   *
   * @param array $facets
   *   The facets.
   */
  public function setFacets(array $facets): SearchApiResponse {
    $this->facets = $facets;
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function facets(): array {
    if (!$this->facets || !$this->facetMapping) {
      return [];
    }

    if (!$this->result) {
      $this->result = $this->query->execute();
    }

    $facets = [];

    $facetData = $this->result->getExtraData('search_api_facets');
    foreach ($facetData as $facetFieldId => $facetResults) {
      $facets[] = [
        'key' => $this->facetMapping[$facetFieldId],
        'values' => $this->processFacetResults($this->facets[$facetFieldId], $facetResults),
      ];
    }

    return $facets;
  }

  /**
   * Get search result items.
   *
   * @return array|\GraphQL\Deferred
   *   The search result items.
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function items(): array|Deferred {
    if (!$this->result) {
      $this->result = $this->query->execute();
    }

    $ids = array_map(static function ($item) {
      return $item->getId();
    }, $this->result->getResultItems());

    $ids = array_unique($ids);

    if (empty($ids)) {
      return [];
    }

    $callback = $this->buffer->add(
      $this->query->getIndex()->id(),
      array_values($ids)
    );

    return new Deferred(function () use ($callback) {
      return $callback();
    });
  }

  /**
   * Returns the total results.
   *
   * @return int
   *   The total results.
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function total(): int {
    $query = clone $this->query;
    $query->range(0, NULL);
    $result = $query->execute();

    return (int) $result->getResultCount();
  }

  /**
   * Handles processing of facet values.
   *
   * @param \Drupal\facets\Entity\Facet $facet
   *   The facet to process.
   * @param array $facetResults
   *   The facet results.
   *
   * @return array
   *   The processed facet results.
   */
  private function processFacetResults(
    Facet $facet,
    array $facetResults,
  ): array {
    // First process facet results which contain filter like filter=""9"".
    // @see Drupal\facets\Plugin\facets\query_type\SearchApiString#build().
    foreach ($facetResults as $i => $facetResult) {
      $facetResult['filter'] = $facetResult['filter'] ?? '';

      if ($facetResult['filter'][0] === '"') {
        $facetResult['filter'] = substr($facetResult['filter'], 1);
      }
      if ($facetResult['filter'][strlen($facetResult['filter']) - 1] === '"') {
        $facetResult['filter'] = substr($facetResult['filter'], 0, -1);
      }

      $facetResults[$i] = $facetResult;
    }

    return $this->processFacetResultsFromFieldConfig($facet, $facetResults);
  }

  /**
   * Populates label for facet values from allowed options field config.
   *
   * @param \Drupal\facets\Entity\Facet $facet
   *   The facet.
   * @param array $facetResults
   *   The facet results.
   *
   * @return array
   *   The processed facet results.
   */
  private function processFacetResultsFromFieldConfig(
    Facet $facet,
    array $facetResults,
  ): array {
    if (!$this->bundle) {
      return $facetResults;
    }

    $fieldName = $facet->getFieldIdentifier();
    $fieldConfig = $this->entityFieldManager->getFieldDefinitions('node', $this->bundle);

    if (isset($fieldConfig[$fieldName])) {
      $allowedValues = options_allowed_values($fieldConfig[$fieldName]->getFieldStorageDefinition());

      // Use order of allowedValues.
      foreach ($facetResults as $key => $facetResult) {
        $facetResults[$key]['label'] = $allowedValues[$facetResult['filter']] ?? $facetResult['filter'];
        $facetResults[$key]['value'] = $facetResult['filter'];
      }

      $allowedValueKeys = array_keys($allowedValues);
      usort($facetResults, function ($a, $b) use ($allowedValueKeys) {
        $indexA = array_search($a['filter'], $allowedValueKeys, TRUE);
        $indexB = array_search($b['filter'], $allowedValueKeys, TRUE);

        return $indexA < $indexB ? -1 : 1;
      });
    }

    return $facetResults;
  }

}
