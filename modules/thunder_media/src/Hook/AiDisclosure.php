<?php

namespace Drupal\thunder_media\Hook;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\thunder_media\AiDisclosureWriterInterface;

/**
 * Embeds AI-disclosure metadata into image files on save.
 */
class AiDisclosure {

  use LoggerChannelTrait;

  public function __construct(
    protected readonly AiDisclosureWriterInterface $writer,
    protected readonly FileSystemInterface $fileSystem,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_presave() for media entities.
   */
  #[Hook('media_presave')]
  public function mediaPresave(MediaInterface $media): void {
    if ($media->bundle() !== 'image'
      || !$media->isDefaultTranslation()
      || !$media->hasField('field_digital_source_type')
      || !$media->hasField('field_image')
    ) {
      return;
    }

    $file = $media->get('field_image')->entity;
    if (!$file instanceof FileInterface) {
      return;
    }

    $real_path = $this->fileSystem->realpath($file->getFileUri());
    if (!$real_path) {
      $this->getLogger('thunder_media')->notice('Cannot write AI-disclosure metadata for @uri: not a local file.', ['@uri' => $file->getFileUri()]);
      return;
    }

    $term = $media->get('field_digital_source_type')->value;

    $original = $media->getOriginal();
    if ($original instanceof MediaInterface) {
      $term_changed = $original->get('field_digital_source_type')->value !== $term;
      $image_changed = $original->get('field_image')->target_id !== $file->id();
    }
    else {
      // New media has no prior state, so a set disclosure always needs writing.
      $term_changed = (bool) $term;
      $image_changed = TRUE;
    }

    if (!$term && $image_changed) {
      // A newly uploaded file may already carry a disclosure: adopt it.
      $adopted = $this->adoptExistingDisclosure($media, $real_path);
      if ($adopted !== NULL) {
        $term = $adopted;
        $term_changed = TRUE;
      }
    }

    if (!$term_changed && !$image_changed) {
      // Nothing changed since the last save: avoid re-running the writer.
      return;
    }

    if (!$term && !$term_changed) {
      // No disclosure set and it was not just cleared: nothing to remove.
      return;
    }

    if ($term) {
      $this->writer->writeDigitalSourceType($real_path, $term);
    }
    else {
      $this->writer->clearDigitalSourceType($real_path);
    }
  }

  /**
   * Adopts a disclosure already embedded in the file into the entity field.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity being saved.
   * @param string $realPath
   *   Local filesystem path to the image file.
   *
   * @return string|null
   *   The adopted term, or NULL if none was embedded or it is not one of
   *   the field's allowed values.
   */
  protected function adoptExistingDisclosure(MediaInterface $media, string $realPath): ?string {
    $existing = $this->writer->readDigitalSourceType($realPath);
    if ($existing === NULL) {
      return NULL;
    }

    $allowed_values = $media->get('field_digital_source_type')->getFieldDefinition()->getSetting('allowed_values');
    if (!array_key_exists($existing, $allowed_values)) {
      return NULL;
    }

    $media->set('field_digital_source_type', $existing);
    return $existing;
  }

}
