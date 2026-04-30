<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\thunder_gqls\Wrappers\EntityListResponseInterface;

/**
 * Returns the total count from an entity list response.
 *
 * @DataProducer(
 *   id = "entity_list_total",
 *   name = @Translation("Entity List Total"),
 *   description = @Translation("Returns the total count from an entity list response."),
 *   produces = @ContextDefinition("integer",
 *     label = @Translation("Total")
 *   ),
 *   consumes = {
 *     "list" = @ContextDefinition("any",
 *       label = @Translation("Entity list response"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class EntityListTotal extends DataProducerPluginBase {

  /**
   * Resolves the total count.
   *
   * @param \Drupal\thunder_gqls\Wrappers\EntityListResponseInterface $list
   *   The entity list response.
   *
   * @return int
   *   The total count.
   */
  public function resolve(EntityListResponseInterface $list): int {
    return $list->total();
  }

}
