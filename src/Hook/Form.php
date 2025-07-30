<?php

namespace Drupal\thunder\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Generic form hooks implementation for the thunder distribution.
 */
class Form {

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Move content lock unlock button to the more actions array.
    if (!empty($form['actions']['unlock']['#gin_action_item'])) {
      $form['actions']['unlock']['#gin_action_item'] = FALSE;
    }
  }

}
