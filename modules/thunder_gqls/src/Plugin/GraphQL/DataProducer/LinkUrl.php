<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Resolves the URL string from a link array containing a 'uri' key.
 */
#[DataProducer(
  id: "link_url",
  name: new TranslatableMarkup("Link URL"),
  description: new TranslatableMarkup("Resolves the URL string from a link array containing a 'uri' key."),
  produces: new ContextDefinition(
    data_type: "string",
    label: new TranslatableMarkup("URL string")
  ),
  consumes: [
    "link" => new ContextDefinition(
      data_type: "any",
      label: new TranslatableMarkup("Link data")
    ),
  ]
)]
class LinkUrl extends DataProducerPluginBase {

  /**
   * Resolves the URL from a link array.
   *
   * @param mixed $link
   *   The link data array, expected to contain a 'uri' key.
   *
   * @return string
   *   The generated URL string, or empty string if not resolvable.
   */
  public function resolve(mixed $link): string {
    if (!empty($link) && isset($link['uri'])) {
      $urlObject = Url::fromUri($link['uri']);
      return $urlObject->toString(TRUE)->getGeneratedUrl();
    }
    return '';
  }

}
