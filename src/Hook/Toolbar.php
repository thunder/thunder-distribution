<?php

namespace Drupal\thunder\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Generic toolbar hooks implementation for the thunder distribution.
 */
class Toolbar {

  /**
   * Implements hook_toolbar_alter().
   */
  #[Hook('toolbar_alter')]
  public function toolbarAlter(array &$items): void {
    if (!empty($items['admin_toolbar_tools'])) {
      $items['admin_toolbar_tools']['#attached']['library'][] = 'thunder/toolbar.icon';
    }
  }

}
