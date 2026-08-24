<?php

namespace Drupal\thunder_media\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Media widget form hooks for the thunder_media module.
 */
class MediaForm {

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

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

}
