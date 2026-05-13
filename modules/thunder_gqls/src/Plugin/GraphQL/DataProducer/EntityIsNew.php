<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Returns TRUE if the entity has not yet been saved.
 */
#[DataProducer(
  id: "entity_is_new",
  name: new TranslatableMarkup("Entity Is New"),
  description: new TranslatableMarkup("Returns TRUE if the entity has not yet been saved."),
  produces: new ContextDefinition(
    data_type: "boolean",
    label: new TranslatableMarkup("Is new")
  ),
  consumes: [
    "entity" => new ContextDefinition(
      data_type: "entity",
      label: new TranslatableMarkup("Entity")
    ),
  ]
)]
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
