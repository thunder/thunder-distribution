<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Normalizes image derivative data by adding a 'src' alias for the URL.
 */
#[DataProducer(
  id: "image_derivative_src",
  name: new TranslatableMarkup("Image Derivative Src"),
  description: new TranslatableMarkup("Normalizes image derivative data by adding a 'src' alias for the URL."),
  produces: new ContextDefinition(
    data_type: "any",
    label: new TranslatableMarkup("Image derivative data")
  ),
  consumes: [
    "derivative" => new ContextDefinition(
      data_type: "any",
      label: new TranslatableMarkup("Image derivative values")
    ),
  ]
)]
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
