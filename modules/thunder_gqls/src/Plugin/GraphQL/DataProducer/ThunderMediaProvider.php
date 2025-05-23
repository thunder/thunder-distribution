<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\media\MediaInterface;

/**
 * Resolves the provider of a media oEmbed entity or the source ID.
 *
 * @DataProducer(
 *   id = "thunder_media_provider",
 *   name = @Translation("ThuderMediaProvider"),
 *   description = @Translation("Resolves the provider of a media oEmbed entity or the source ID."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("The provider")
 *   ),
 *   consumes = {
 *     "media" = @ContextDefinition("entity",
 *       label = @Translation("Root value")
 *     )
 *   }
 * )
 */
class ThunderMediaProvider extends DataProducerPluginBase {

  /**
   * Resolves the provider of a media oEmbed entity or the source ID.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return string
   *   The provider.
   */
  public function resolve(MediaInterface $media): string {
    return strtolower($media->getSource()->getMetadata($media, 'provider_name') ?: $media->getSource()->getPluginId()) ?: '';
  }

}
