<?php

namespace Drupal\thunder_media\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
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
    protected readonly ConfigFactoryInterface $configFactory,
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

    $term = $media->get('field_digital_source_type')->value;

    $original = $media->getOriginal();
    if ($original instanceof MediaInterface) {
      $original_term = $original->get('field_digital_source_type')->value;
      $term_changed = !$original->get('field_digital_source_type')->equals($media->get('field_digital_source_type'));
      $image_changed = $original->get('field_image')->target_id !== $media->get('field_image')->target_id;
    }
    else {
      // New media has no prior state, so a set disclosure always needs writing.
      $original_term = '';
      $term_changed = (bool) $term;
      $image_changed = TRUE;
    }

    $config = $this->configFactory->get('thunder_media.settings');
    $locked = $config->get('ai_disclosure_upload_only') && !$media->isNew();
    if ($locked && $term_changed) {
      // The field may only be set at upload time: revert any other change,
      // whether from the (disabled) widget or a non-UI write path.
      $term = $original_term;
      $term_changed = FALSE;
      $media->set('field_digital_source_type', $term);
    }

    if (!$term_changed && !$image_changed) {
      // Nothing changed since the last save: avoid re-running the writer.
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

    if (!$term && $image_changed && !$term_changed && !$locked && $config->get('ai_disclosure_auto_detect') !== FALSE) {
      // A newly uploaded file may already carry a disclosure: adopt it.
      $adopted = $this->adoptExistingDisclosure($media, $real_path);
      if ($adopted !== NULL) {
        $term = $adopted;
        $term_changed = TRUE;
      }
    }

    if (!$term && !$term_changed) {
      // No disclosure set and it was not just cleared: nothing to remove.
      return;
    }

    $success = $term
      ? $this->writer->writeDigitalSourceType($real_path, $term)
      : $this->writer->clearDigitalSourceType($real_path);

    if (!$success) {
      if ($this->writer->isAvailable()) {
        // A real write attempt failed: keep the field in sync with the
        // file, not an unwritten value.
        $media->set('field_digital_source_type', $original_term);
      }
      // Else exiftool itself is unavailable: the entity still saves
      // normally, per the documented fallback behavior.
      return;
    }

    // The file's bytes changed on disk: refresh the cached filesize.
    clearstatcache(TRUE, $real_path);
    try {
      $file->save();
    }
    catch (\Exception $e) {
      $this->getLogger('thunder_media')->warning('Could not update the cached filesize for @uri after writing AI-disclosure metadata: @message', [
        '@uri' => $file->getFileUri(),
        '@message' => $e->getMessage(),
      ]);
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

    $field_definition = $media->get('field_digital_source_type')->getFieldDefinition();
    $allowed_values = options_allowed_values($field_definition->getFieldStorageDefinition(), $media);
    if (!array_key_exists($existing, $allowed_values)) {
      return NULL;
    }

    $media->set('field_digital_source_type', $existing);
    return $existing;
  }

}
