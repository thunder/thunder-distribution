<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\media\MediaInterface;

/**
 * Resolves the source field of a media entity.
 *
 * @DataProducer(
 *   id = "media_source_field",
 *   name = @Translation("MediaSourceField"),
 *   description = @Translation("Resolves the source field of a media entity."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("The source field")
 *   ),
 *   consumes = {
 *     "media" = @ContextDefinition("entity",
 *       label = @Translation("Root value")
 *     )
 *   }
 * )
 */
class MediaSourceField extends DataProducerPluginBase {

  /**
   * Resolves the source field of a media entity.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return string
   *   The source field value.
   */
  public function resolve(MediaInterface $media): string {
    return $media->getSource()->getSourceFieldValue($media) ?: '';
  }

}
