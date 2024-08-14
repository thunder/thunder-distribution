<?php

namespace Drupal\thunder_gqls\Wrappers;

use Drupal\facets\Entity\Facet;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use GraphQL\Deferred;

/**
 * SearchApi Result graphql wrapper.
 *
 * @package Drupal\thunder_gqls
 */
class SearchApiResponse implements SearchApiResponseInterface {

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
  protected ?ResultSetInterface $result;

  /**
   * Array of Facets.
   *
   * @var ?array
   */
  private ?array $facets;

  /**
   * Array of Facet mapping.
   *
   * @var ?array
   */
  private ?array $facetMapping;

  /**
   * The bundle for fetching facet field information.
   *
   * @var ?string
   */
  private ?string $bundle;

  /**
   * PlantfinderResult Constructor.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The search api query.
   * @param array|null $facets
   *   The facets.
   * @param array|null $facetMapping
   *   The facet mapping.
   * @param string|null $bundle
   *   The bundle.
   */
  public function __construct(QueryInterface $query, mixed $facets = NULL, ?array $facetMapping = NULL, ?string $bundle = NULL) {
    $this->query = $query;
    $this->result = NULL;
    $this->facets = $facets;
    $this->facetMapping = $facetMapping;
    $this->bundle = $bundle;
  }

  /**
   * Get search facets.
   *
   * @return array
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
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function items() {
    if (!$this->result) {
      $this->result = $this->query->execute();
    }

    // @phpstan-ignore-next-line
    $searchApiResultBuffer = \Drupal::service(
      'thunder_gqls.buffer.search_api_result'
    );

    $ids = array_map(function ($item) {
      return $item->getId();
    }, $this->result->getResultItems());

    $ids = array_unique($ids);

    if (empty($ids)) {
      return [];
    }

    $callback = $searchApiResultBuffer->add(
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
   */
  private function processFacetResultsFromFieldConfig(
    Facet $facet,
    array $facetResults,
  ): array {
    if (!$this->bundle) {
      return $facetResults;
    }

    $fieldName = $facet->getFieldIdentifier();
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
    // @phpstan-ignore-next-line
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fieldConfig = $entityFieldManager->getFieldDefinitions('node', $this->bundle);

    if (isset($fieldConfig[$fieldName])) {
      $allowedValues = options_allowed_values($fieldConfig[$fieldName]->getFieldStorageDefinition());

      // Use order of allowedValues.
      foreach ($facetResults as $key => $facetResult) {
        $facetResults[$key]['label'] = $allowedValues[$facetResult['filter']] ?? $facetResult['filter'];
        $facetResults[$key]['value'] = $facetResult['filter'];
      }

      $allowedValueKeys = array_keys($allowedValues);
      usort($facetResults, function ($a, $b) use ($allowedValueKeys) {
        $indexA = array_search($a['filter'], $allowedValueKeys);
        $indexB = array_search($b['filter'], $allowedValueKeys);

        return $indexA < $indexB ? -1 : 1;
      });
    }

    return $facetResults;
  }

}
