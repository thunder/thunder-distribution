<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Returns a single item or the full list from entity reference results.
 *
 * @DataProducer(
 *   id = "entity_reference_item",
 *   name = @Translation("Entity Reference Item"),
 *   description = @Translation("Returns a single item or the full list from entity reference results."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Entity or entity list")
 *   ),
 *   consumes = {
 *     "list" = @ContextDefinition("any",
 *       label = @Translation("List of entities"),
 *       required = TRUE
 *     ),
 *     "multiple" = @ContextDefinition("boolean",
 *       label = @Translation("Multiple values"),
 *       default_value = TRUE,
 *       required = FALSE
 *     )
 *   }
 * )
 */
class EntityReferenceItem extends DataProducerPluginBase {

  /**
   * Resolves the entity reference result.
   *
   * @param mixed $list
   *   The list of referenced entities.
   * @param bool $multiple
   *   Whether to return the full list or only the first item.
   *
   * @return mixed
   *   The full list or the first item.
   */
  public function resolve(mixed $list, bool $multiple = TRUE): mixed {
    if ($multiple) {
      return $list;
    }
    return $list[0] ?? NULL;
  }

}
