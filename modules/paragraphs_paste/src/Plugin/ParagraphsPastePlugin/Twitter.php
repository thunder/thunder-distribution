<?php

namespace Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\paragraphs_paste\ParagraphsPastePluginBase;

/**
 * Defines the "twitter" plugin.
 *
 * @ParagraphsPastePlugin(
 *   id = "twitter",
 *   label = @Translation("Twitter"),
 *   module = "paragraphs_paste",
 *   weight = 0
 * )
 */
class Twitter extends ParagraphsPastePluginBase {

  /**
   * {@inheritdoc}
   */
  public function build($input) {
    // Create media entity.
    $media_type = $this->entityTypeManager->getDefinition('media');
    $media_entity = $this->entityTypeManager->getStorage('media')
      ->create([
        $media_type->getKey('bundle') => 'twitter',
      ]);

    $media_entity->set('field_url', $input);
    $media_entity->save();

    // Create paragraph entity and reference media entity.
    $entity_type = $this->entityTypeManager->getDefinition('paragraph');

    $paragraph_entity = $this->entityTypeManager->getStorage('paragraph')
      ->create([
        $entity_type->getKey('bundle') => 'twitter',
      ]);

    $paragraph_entity->set('field_media', $media_entity);

    return $paragraph_entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input) {
    $regex = '/(?:http:\/\/)?(?:www\.)?twitter\.com\/(?:(?:\w)*#!\/)?(?:pages\/)?(?:[\w\-]*\/)*([\w\-]*)/';
    return preg_match($regex, $input);
  }

}
