<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Normalizes image derivative data by adding a 'src' alias for the URL.
 *
 * @DataProducer(
 *   id = "image_derivative_src",
 *   name = @Translation("Image Derivative Src"),
 *   description = @Translation("Normalizes image derivative data by adding a 'src' alias for the URL."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Image derivative data")
 *   ),
 *   consumes = {
 *     "derivative" = @ContextDefinition("any",
 *       label = @Translation("Image derivative values"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ImageDerivativeSrc extends DataProducerPluginBase {

  /**
   * Adds 'src' as an alias for 'url' in image derivative data.
   *
   * @param mixed $derivative
   *   The image derivative values array.
   *
   * @return mixed
   *   The derivative array with 'src' added when 'url' is present.
   */
  public function resolve(mixed $derivative): mixed {
    if (!empty($derivative['url'])) {
      return $derivative + ['src' => $derivative['url']];
    }
    return $derivative;
  }

}
