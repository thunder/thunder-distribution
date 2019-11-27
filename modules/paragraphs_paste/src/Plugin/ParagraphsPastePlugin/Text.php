<?php

namespace Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\paragraphs_paste\ParagraphsPastePluginBase;

/**
 * Defines the "text" plugin.
 *
 * @ParagraphsPastePlugin(
 *   id = "text",
 *   label = @Translation("Text"),
 *   module = "paragraphs_paste",
 *   weight = -1
 * )
 */
class Text extends ParagraphsPastePluginBase {

  /**
   * {@inheritdoc}
   */
  public function build($input) {
    $target_type = 'paragraph';
    $bundle = 'text';

    $entity_type = $this->entityTypeManager->getDefinition($target_type);

    $paragraph_entity = $this->entityTypeManager->getStorage($target_type)
      ->create([
        $entity_type->getKey('bundle') => $bundle,
      ]);

    $paragraph_entity->set('field_text', $input);

    return $paragraph_entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input, array $definition) {
    // Catch all content.
    return !empty(trim($input));
  }

}
