<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Returns the input value or a fallback if the input is falsy.
 *
 * @DataProducer(
 *   id = "fallback_value",
 *   name = @Translation("Fallback Value"),
 *   description = @Translation("Returns the input value or a fallback if the input is falsy."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Value")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = @Translation("Input value"),
 *       required = TRUE
 *     ),
 *     "fallback" = @ContextDefinition("any",
 *       label = @Translation("Fallback value"),
 *       default_value = NULL,
 *       required = FALSE
 *     )
 *   }
 * )
 */
class FallbackValue extends DataProducerPluginBase {

  /**
   * Resolves the value with fallback.
   *
   * @param mixed $input
   *   The input value.
   * @param mixed $fallback
   *   The fallback value used when input is falsy.
   *
   * @return mixed
   *   The input value or the fallback.
   */
  public function resolve(mixed $input, mixed $fallback = NULL): mixed {
    return $input ?: $fallback;
  }

}
