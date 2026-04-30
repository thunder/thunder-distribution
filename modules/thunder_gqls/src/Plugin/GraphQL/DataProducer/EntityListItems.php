<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\thunder_gqls\Wrappers\EntityListResponseInterface;
use GraphQL\Deferred;

/**
 * Returns the items from an entity list response.
 *
 * @DataProducer(
 *   id = "entity_list_items",
 *   name = @Translation("Entity List Items"),
 *   description = @Translation("Returns the items from an entity list response."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Items")
 *   ),
 *   consumes = {
 *     "list" = @ContextDefinition("any",
 *       label = @Translation("Entity list response"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class EntityListItems extends DataProducerPluginBase {

  /**
   * Resolves the items.
   *
   * @param \Drupal\thunder_gqls\Wrappers\EntityListResponseInterface $list
   *   The entity list response.
   *
   * @return array|\GraphQL\Deferred
   *   The items.
   */
  public function resolve(EntityListResponseInterface $list): array|Deferred {
    return $list->items();
  }

}
