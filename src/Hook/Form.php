<?php

namespace Drupal\thunder\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\thunder\ActiveThemesTrait;

/**
 * Generic form hooks implementation for the thunder distribution.
 */
class Form {

  use ActiveThemesTrait;

  public function __construct(
    protected readonly ThemeManagerInterface $themeManager,
  ) {}

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Move content lock unlock button to the more actions array.
    if (!empty($form['actions']['unlock']['#gin_action_item'])) {
      $form['actions']['unlock']['#gin_action_item'] = FALSE;
    }

    // Media library previews only appear inside forms (widget, modal, grid).
    if (isset($this->getActiveThemes()['gin'])) {
      $form['#attached']['library'][] = 'thunder/media_library.gin';
    }
  }

}
