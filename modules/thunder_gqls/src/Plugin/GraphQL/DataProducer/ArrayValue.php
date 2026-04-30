<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

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
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ArrayValue extends DataProducerPluginBase {

  /**
   * Resolves the value for the given key.
   *
   * @param mixed $input
   *   The input array.
   * @param string $key
   *   The key to look up.
   *
   * @return mixed
   *   The value or NULL if not present.
   */
  public function resolve(mixed $input, string $key): mixed {
    if (!is_array($input)) {
      return NULL;
    }
    return $input[$key] ?? NULL;
  }

}
