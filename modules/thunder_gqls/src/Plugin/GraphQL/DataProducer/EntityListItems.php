<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\thunder_gqls\Wrappers\EntityListResponseInterface;
use GraphQL\Deferred;

/**
 * Returns the items from an entity list response.
 */
#[DataProducer(
  id: "entity_list_items",
  name: new TranslatableMarkup("Entity List Items"),
  description: new TranslatableMarkup("Returns the items from an entity list response."),
  produces: new ContextDefinition(
    data_type: "any",
    label: new TranslatableMarkup("Items")
  ),
  consumes: [
    "list" => new ContextDefinition(
      data_type: "any",
      label: new TranslatableMarkup("Entity list response")
    ),
  ]
)]
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
