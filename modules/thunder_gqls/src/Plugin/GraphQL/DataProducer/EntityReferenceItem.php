<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Returns a single item or the full list from entity reference results.
 */
#[DataProducer(
  id: "entity_reference_item",
  name: new TranslatableMarkup("Entity Reference Item"),
  description: new TranslatableMarkup("Returns a single item or the full list from entity reference results."),
  produces: new ContextDefinition(
    data_type: "any",
    label: new TranslatableMarkup("Entity or entity list")
  ),
  consumes: [
    "list" => new ContextDefinition(
      data_type: "any",
      label: new TranslatableMarkup("List of entities")
    ),
    "multiple" => new ContextDefinition(
      data_type: "boolean",
      label: new TranslatableMarkup("Multiple values"),
      required: FALSE,
      default_value: TRUE
    ),
  ]
)]
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
