<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Returns the value for a given key from an array.
 *
 * @DataProducer(
 *   id = "array_value",
 *   name = @Translation("Array Value"),
 *   description = @Translation("Returns the value for a given key from an array."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Value")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = @Translation("Input array"),
 *       required = TRUE
 *     ),
 *     "key" = @ContextDefinition("string",
 *       label = @Translation("Key"),
 *       required = FALSE
 *     )
 *   }
 * )
 */
class ArrayValue extends DataProducerPluginBase {

  /**
   * Resolves the value for the given key.
   *
   * When no key is provided, uses the GraphQL field name (mirroring
   * graphql-php's defaultFieldResolver behaviour).
   *
   * @param mixed $input
   *   The input array.
   * @param string|null $key
   *   The key to look up, or NULL to use the field name.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $field
   *   The current field context.
   *
   * @return mixed
   *   The value or NULL if not present.
   */
  public function resolve(mixed $input, ?string $key, FieldContext $field): mixed {
    if (!is_array($input)) {
      return NULL;
    }
    return $input[$key ?? $field->getFieldName()] ?? NULL;
  }

}
