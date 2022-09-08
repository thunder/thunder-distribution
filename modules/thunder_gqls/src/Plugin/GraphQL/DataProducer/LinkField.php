<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Url;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Plugin to resolve all the links for an entity.
 *
 * @DataProducer(
 *   id = "link_field",
 *   name = @Translation("Field link url"),
 *   description = @Translation("Returns url of a link field."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Url")
 *   ),
 *   consumes = {
 *     "field" = @ContextDefinition("map",
 *       label = @Translation("Link field values")
 *     ),
 *     "property" = @ContextDefinition("string",
 *       label = @Translation("The property of the link field")
 *     )
 *   }
 * )
 */
class LinkField extends DataProducerPluginBase {

  /**
   * Resolves the url of a link.
   *
   * @param array $field
   *   The link field.
   * @param string $property
   *   The property of the link field.
   *
   * @return string
   *   The url.
   */
  public function resolve(array $field, string $property): string {
    if (isset($field[0])) {
      $field = $field[0];
    }

    if ($property === 'title') {
      return $field[$property] ?? '';
    }

    if ($property === 'uri') {
      if (!empty($field) && isset($field[$property])) {
        $urlObject = Url::fromUri($field[$property]);
        $url = $urlObject->toString(TRUE)->getGeneratedUrl();
      }
      return $url ?? '';
    }

    return '';
  }

}
