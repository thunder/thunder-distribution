<?php

namespace Drupal\thunder_media\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\thunder_media\AiDisclosureWriterInterface;

/**
 * Media widget form hooks for the thunder_media module.
 */
class MediaForm {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly AiDisclosureWriterInterface $writer,
    protected readonly FileSystemInterface $fileSystem,
  ) {}

  /**
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   *
   * Disables the "AI disclosure" field once uploaded, if so configured.
   * On a new, unsaved media item, pre-selects a disclosure already embedded
   * in the just-uploaded file, so it is visible before the first save.
   */
  #[Hook('field_widget_single_element_options_select_form_alter')]
  public function fieldWidgetSingleElementOptionsSelectFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    $items = $context['items'];
    if ($items->getFieldDefinition()->getName() !== 'field_digital_source_type') {
      return;
    }

    $media = $items->getEntity();
    if ($media->isNew()) {
      $this->prefillFromUpload($element, $form_state, $media, $items);
      return;
    }

    $config = $this->configFactory->get('thunder_media.settings');
    if ($config->get('ai_disclosure_upload_only')) {
      $element['#disabled'] = TRUE;
    }
  }

  /**
   * Pre-selects a disclosure already embedded in the just-uploaded file.
   */
  protected function prefillFromUpload(array &$element, FormStateInterface $form_state, MediaInterface $media, FieldItemListInterface $items): void {
    if (!$items->isEmpty() || !$media->hasField('field_image')) {
      return;
    }

    $config = $this->configFactory->get('thunder_media.settings');
    if ($config->get('ai_disclosure_auto_detect') === FALSE) {
      return;
    }

    $file = $media->get('field_image')->entity;
    if (!$file instanceof FileInterface) {
      return;
    }

    // Reading is a subprocess call: only attempt it once per uploaded file,
    // not on every subsequent AJAX rebuild of the form.
    $state_key = ['thunder_media_ai_disclosure_prefill', $file->id()];
    if ($form_state->get($state_key)) {
      return;
    }
    $form_state->set($state_key, TRUE);

    $real_path = $this->fileSystem->realpath($file->getFileUri());
    if (!$real_path) {
      return;
    }

    $existing = $this->writer->readDigitalSourceType($real_path);
    if ($existing === NULL) {
      return;
    }

    $field_definition = $items->getFieldDefinition();
    $allowed_values = options_allowed_values($field_definition->getFieldStorageDefinition(), $media);
    if (array_key_exists($existing, $allowed_values)) {
      $element['#default_value'] = $existing;
    }
  }

}
