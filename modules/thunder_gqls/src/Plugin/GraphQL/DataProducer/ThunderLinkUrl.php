<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Url;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Resolves the URL string from a link array containing a 'uri' key.
 *
 * @DataProducer(
 *   id = "thunder_link_url",
 *   name = @Translation("Thunder Link URL"),
 *   description = @Translation("Resolves the URL string from a link array containing a 'uri' key."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("URL string")
 *   ),
 *   consumes = {
 *     "link" = @ContextDefinition("any",
 *       label = @Translation("Link data"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ThunderLinkUrl extends DataProducerPluginBase {

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
