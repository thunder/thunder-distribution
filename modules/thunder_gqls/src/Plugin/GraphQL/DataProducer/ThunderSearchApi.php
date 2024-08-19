<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\thunder_gqls\Wrappers\SearchApiResponse;

/**
 * Produces an entity list.
 *
 * @DataProducer(
 *   id = "thunder_search_api",
 *   name = @Translation("Search api query"),
 *   description = @Translation("Loads a list of entities from search api."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Entity list")
 *   ),
 *   consumes = {
 *     "limit" = @ContextDefinition("integer",
 *       label = @Translation("Limit"),
 *       required = TRUE
 *     ),
 *     "offset" = @ContextDefinition("integer",
 *       label = @Translation("Offset"),
 *       required = TRUE
 *     ),
 *     "index" = @ContextDefinition("string",
 *       label = @Translation("Search Api Index"),
 *       required = TRUE
 *     ),
 *     "conditions" = @ContextDefinition("map",
 *       label = @Translation("Filter conditions"),
 *       multiple = TRUE,
 *       required = FALSE,
 *       default_value = {}
 *      ),
 *     "search" = @ContextDefinition("string",
 *       label = @Translation("Search"),
 *       required = FALSE
 *     ),
 *   }
 * )
 */
class ThunderSearchApi extends ThunderSearchApiProducerBase {

  /**
   * Resolve entity query.
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
   * @return \Drupal\thunder_gqls\Wrappers\SearchApiResponse|null
   *   SearchApi result.
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  protected function resolve(
    int $limit,
    int $offset,
    string $index,
    ?array $conditions,
    ?string $search,
    FieldContext $cacheContext,
  ): ?SearchApiResponse {
    $query = $this->buildBaseQuery(
      $limit,
      $offset,
      $index,
      $conditions,
      $search,
      $cacheContext
    );

    return $this->searchApiResponse($query);
  }

}
