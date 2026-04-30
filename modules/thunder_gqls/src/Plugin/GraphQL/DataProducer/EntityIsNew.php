<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Returns TRUE if the entity has not yet been saved.
 *
 * @DataProducer(
 *   id = "entity_is_new",
 *   name = @Translation("Entity Is New"),
 *   description = @Translation("Returns TRUE if the entity has not yet been saved."),
 *   produces = @ContextDefinition("boolean",
 *     label = @Translation("Is new")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class EntityIsNew extends DataProducerPluginBase {

  /**
   * Resolves whether the entity is new.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if the entity has not been saved yet, FALSE otherwise.
   */
  public function resolve(EntityInterface $entity): bool {
    return $entity->isNew();
  }

}
