<?php

namespace Drupal\thunder_media\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Media widget form hooks for the thunder_media module.
 */
class MediaForm {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   *
   * Add process function to hide 'remove' button of image field widget on
   * inline entity forms.
   */
  #[Hook('field_widget_single_element_image_focal_point_form_alter')]
  public function fieldWidgetSingleElementImageFocalPointFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    $config = $this->configFactory->get('thunder_media.settings');

    if (!$config->get('enable_filefield_remove_button')) {
      $type = NULL;
      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface|null $form_display */
      $form_display = !empty($form_state->getStorage()['form_display']) ? $form_state->getStorage()['form_display'] : NULL;
      if ($form_display && $form_display->getTargetEntityTypeId() === 'media') {
        $type = $form_display->getTargetBundle();
      }
      elseif (isset($context['form']['#type']) && $context['form']['#type'] === 'inline_entity_form' && $context['form']['#entity_type'] === 'media') {
        $type = $context['form']['#bundle'];
      }
      if ($type) {
        /** @var \Drupal\media\MediaTypeInterface $type */
        $type = $this->entityTypeManager->getStorage('media_type')->load($type);
        if ($type->get('source_configuration')['source_field'] == $element['#field_name']) {
          $element['#process'][] = [self::class, 'inlineEntityFormImageWidgetProcess'];
        }
      }
    }
  }

  /**
   * Implements hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   *
   * Disables the "AI disclosure" field once uploaded, if so configured.
   */
  #[Hook('field_widget_single_element_options_select_form_alter')]
  public function fieldWidgetSingleElementOptionsSelectFormAlter(array &$element, FormStateInterface $form_state, array $context): void {
    $items = $context['items'];
    if ($items->getFieldDefinition()->getName() !== 'field_digital_source_type') {
      return;
    }

    $media = $items->getEntity();
    if ($media->isNew()) {
      return;
    }

    $config = $this->configFactory->get('thunder_media.settings');
    if ($config->get('ai_disclosure_upload_only')) {
      $element['#disabled'] = TRUE;
    }
  }

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_media_library_media_modify_widget_form_alter')]
  public function fieldWidgetCompleteMediaLibraryMediaModifyWidgetFormAlter(array &$field_widget_complete_form, FormStateInterface $form_state, array $context): void {
    // Add custom styling.
    $field_widget_complete_form['#attached']['library'][] = 'thunder_media/media_library.widget';
  }

  /**
   * Process callback to hide the 'Remove' button on image widget forms.
   */
  public static function inlineEntityFormImageWidgetProcess(array $element, FormStateInterface $form_state, array $form): array {
    if (isset($element['remove_button'])) {
      $element['remove_button']['#access'] = FALSE;
    }

    return $element;
  }

}
