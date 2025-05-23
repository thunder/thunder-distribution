<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\media\MediaInterface;

/**
 * Resolves the provider of an embed media entity.
 *
 * @DataProducer(
 *   id = "oembed_media_provider",
 *   name = @Translation("EmbedMediaProvider"),
 *   description = @Translation("Resolves the provider of an embed media entity."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("The embed provider")
 *   ),
 *   consumes = {
 *     "media" = @ContextDefinition("entity",
 *       label = @Translation("Root value")
 *     )
 *   }
 * )
 */
class OembedMediaProvider extends DataProducerPluginBase {

  /**
   * Resolve the focal point positions.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return string
   *   The provider.
   */
  public function resolve(MediaInterface $media): string {
    return strtolower($media->getSource()->getMetadata($media, 'provider_name')) ?: '';
  }

}
