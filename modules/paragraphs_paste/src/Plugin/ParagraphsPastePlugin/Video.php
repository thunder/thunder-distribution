<?php

namespace Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\paragraphs_paste\ParagraphsPastePluginBase;

/**
 * Defines the "video" plugin.
 *
 * @ParagraphsPastePlugin(
 *   id = "video",
 *   label = @Translation("Video"),
 *   module = "paragraphs_paste",
 *   weight = 0
 * )
 */
class Video extends ParagraphsPastePluginBase {

  /**
   * {@inheritdoc}
   */
  public function build($input) {
    // Create media entity.
    $media_type = $this->entityTypeManager->getDefinition('media');
    $media_entity = $this->entityTypeManager->getStorage('media')
      ->create([
        $media_type->getKey('bundle') => 'video',
      ]);

    $media_entity->set('field_media_video_embed_field', $input);
    $media_entity->save();

    // Create paragraph entity and reference media entity.
    $entity_type = $this->entityTypeManager->getDefinition('paragraph');

    $paragraph_entity = $this->entityTypeManager->getStorage('paragraph')
      ->create([
        $entity_type->getKey('bundle') => 'video',
      ]);

    $paragraph_entity->set('field_video', $media_entity);

    return $paragraph_entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input) {
    // Only youtube and vimeo are currently supported.
    $regex = '/(?:https?:\/\/)?(?:www\.)?(?:vimeo\.com\/\w+\/?)|(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))[^&\s]+(?:\S+)?/';
    return preg_match($regex, $input);
  }

}
