<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Views hook implementations for the thunder_ai_prompt_management module.
 */
final class ViewsHooks {

  /**
   * Implements hook_views_data_alter().
   *
   * The generic EntityViewsData handler maps the "ai_task" base field to a
   * plain string filter, since it has no attached Field API storage to infer
   * an entity reference filter from. Point it at the proper filter plugin so
   * it exposes a select list of AI Task labels instead of a raw ID textfield.
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data): void {
    $data['ai_prompt_content']['ai_task']['filter']['id'] = 'entity_reference';
    $data['ai_prompt_content']['model']['filter']['id'] = 'ai_prompt_model';
  }

}
