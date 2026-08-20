<?php

namespace Drupal\thunder_media\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Media rendering hooks for the thunder_media module.
 */
class Media {

  /**
   * Implements hook_preprocess_media().
   */
  #[Hook('preprocess_media')]
  public function preprocessMedia(array &$variables): void {
    // Remove contextual links from preview in node form.
    if ($variables['elements']['#view_mode'] === 'paragraph_preview' && isset($variables['title_suffix']['contextual_links'])) {
      unset($variables['title_suffix']['contextual_links']);
      unset($variables['elements']['#contextual_links']);
      $variables['attributes']['class'] = array_diff($variables['attributes']['class'], ['contextual-region']);
    }
  }

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page): void {
    $page['#attached']['library'][] = 'thunder_media/gallery';
  }

  /**
   * Implements hook_ENTITY_TYPE_view_alter() for media entities.
   */
  #[Hook('media_view_alter')]
  public function mediaViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display): void {
    if ($entity->bundle() === 'gallery' && $display->getMode() === 'paragraph_preview') {
      $build['#attached']['library'][] = 'thunder_media/gallery.paragraph_preview';
      foreach (Element::children($build['field_media_images']) as $key) {
        if ($key > 5) {
          $build['field_media_images'][$key]['#access'] = FALSE;
        }
      }
    }
  }

}
