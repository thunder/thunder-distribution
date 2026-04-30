<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Returns TRUE if the entity has been saved (is not new).
 *
 * @DataProducer(
 *   id = "entity_is_not_new",
 *   name = @Translation("Entity Is Not New"),
 *   description = @Translation("Returns TRUE if the entity has been saved (is not new)."),
 *   produces = @ContextDefinition("boolean",
 *     label = @Translation("Is not new")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class EntityIsNotNew extends DataProducerPluginBase {

  /**
   * Resolves whether the entity is not new.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the entity has been saved, FALSE if it is new.
   */
  public function resolve(EntityInterface $entity): bool {
    return !$entity->isNew();
  }

}
