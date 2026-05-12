<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\thunder_gqls\Wrappers\EntityListResponseInterface;

/**
 * Returns the total count from an entity list response.
 */
#[DataProducer(
  id: "entity_list_total",
  name: new TranslatableMarkup("Entity List Total"),
  description: new TranslatableMarkup("Returns the total count from an entity list response."),
  produces: new ContextDefinition(
    data_type: "integer",
    label: new TranslatableMarkup("Total")
  ),
  consumes: [
    "list" => new ContextDefinition(
      data_type: "any",
      label: new TranslatableMarkup("Entity list response")
    ),
  ]
)]
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
