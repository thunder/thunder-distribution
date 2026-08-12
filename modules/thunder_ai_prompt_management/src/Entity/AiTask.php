<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\thunder_ai_prompt_management\AiTaskInterface;
use Drupal\thunder_ai_prompt_management\AiTaskListBuilder;
use Drupal\thunder_ai_prompt_management\Form\AiTaskForm;

/**
 * Defines the ai task entity type.
 */
#[ConfigEntityType(
  id: 'ai_task',
  label: new TranslatableMarkup('AiTask'),
  label_collection: new TranslatableMarkup('AiTasks'),
  label_singular: new TranslatableMarkup('ai task'),
  label_plural: new TranslatableMarkup('ai tasks'),
  config_prefix: 'ai_task',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => AiTaskListBuilder::class,
    'form' => [
      'add' => AiTaskForm::class,
      'edit' => AiTaskForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'collection' => '/admin/structure/ai-task',
    'add-form' => '/admin/structure/ai-task/add',
    'edit-form' => '/admin/structure/ai-task/{ai_task}',
    'delete-form' => '/admin/structure/ai-task/{ai_task}/delete',
  ],
  admin_permission: 'administer ai_task',
  label_count: [
    'singular' => '@count ai task',
    'plural' => '@count ai tasks',
  ],
  config_export: [
    'id',
    'label',
    'description',
  ],
)]
final class AiTask extends ConfigEntityBase implements AiTaskInterface {

  /**
   * The example ID.
   */
  protected string $id;

  /**
   * The example label.
   */
  protected string $label;

  /**
   * The example description.
   */
  protected string $description;

}
