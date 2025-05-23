<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\taxonomy\TermInterface;
use Drupal\thunder_gqls\Wrappers\EntityListResponseInterface;

/**
 * The channel list producer class.
 *
 * @DataProducer(
 *   id = "entities_with_term",
 *   name = @Translation("Term entity list"),
 *   description = @Translation("Loads a list of entities for a term."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Term entity list")
 *   ),
 *   consumes = {
 *     "term" = @ContextDefinition("entity",
 *       label = @Translation("Term entity")
 *     ),
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Entity type")
 *     ),
 *     "bundles" = @ContextDefinition("string",
 *       label = @Translation("Entity bundles"),
 *       multiple = TRUE,
 *       required = FALSE,
 *       default_value = {}
 *     ),
 *     "field" = @ContextDefinition("string",
 *       label = @Translation("The term reference field"),
 *       multiple = FALSE,
 *       required = TRUE
 *     ),
 *     "offset" = @ContextDefinition("integer",
 *       label = @Translation("Offset"),
 *       required = FALSE,
 *       default_value = 0
 *     ),
 *     "limit" = @ContextDefinition("integer",
 *       label = @Translation("Limit"),
 *       required = FALSE,
 *       default_value = 100
 *     ),
 *     "conditions" = @ContextDefinition("map",
 *       label = @Translation("Filter conditions"),
 *       multiple = TRUE,
 *       required = FALSE,
 *       default_value = {}
 *     ),
 *     "languages" = @ContextDefinition("string",
 *       label = @Translation("Entity languages"),
 *       multiple = TRUE,
 *       required = FALSE,
 *       default_value = {}
 *     ),
 *     "sortBy" = @ContextDefinition("map",
 *       label = @Translation("Sorts"),
 *       multiple = TRUE,
 *       default_value = {},
 *       required = FALSE
 *     ),
 *     "depth" = @ContextDefinition("integer",
 *       label = @Translation("Depth"),
 *       required = FALSE,
 *       default_value = 0
 *     ),
 *   }
 * )
 */
class EntitiesWithTerm extends ThunderEntityListProducerBase {

  /**
   * Build entity query for entities, that reference a specific term.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The term entity interface.
   * @param string $type
   *   Entity type.
   * @param string[] $bundles
   *   List of bundles to be filtered.
   * @param string $field
   *   The term reference field of the bundle.
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
   * @param int $depth
   *   The depth of children of the term.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $cacheContext
   *   The caching context related to the current field.
   *
   * @return \Drupal\thunder_gqls\Wrappers\EntityListResponse
   *   Base entity list response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resolve(TermInterface $term, string $type, array $bundles, string $field, int $offset, int $limit, array $conditions, array $languages, array $sortBy, int $depth, FieldContext $cacheContext): EntityListResponseInterface {
    $conditions = array_merge($conditions, $this->getConditions($term, $field, $depth));

    $query = $this->query(
      $type,
      $bundles,
      $offset,
      $limit,
      $conditions,
      $languages,
      $sortBy,
      $cacheContext
    );

    return $this->entityListResponse($query);
  }

  /**
   * Get conditions for term query.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The taxonomy term.
   * @param string $field
   *   The term reference field.
   * @param int $depth
   *   The depth of term relations.
   *
   * @return array[]
   *   The entity query conditions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getConditions(TermInterface $term, string $field, int $depth): array {
    $termIds = [$term->id()];

    if ($depth > 0) {
      $terms = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadTree($term->bundle(), $term->id(), $depth);
      $termIds = array_merge($termIds, array_column($terms, 'tid'));
    }

    return [
      [
        'field' => $field,
        'value' => $termIds,
        'operator' => 'IN',
      ],
    ];
  }

}
