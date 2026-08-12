<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\thunder_ai_prompt_management\AiPromptTypeInterface;
use Drupal\thunder_ai_prompt_management\AiPromptTypeListBuilder;
use Drupal\thunder_ai_prompt_management\Form\AiPromptTypeForm;

/**
 * Defines the ai prompt type entity type.
 */
#[ConfigEntityType(
  id: 'ai_prompt_content_type',
  label: new TranslatableMarkup('AI Prompt Type'),
  label_collection: new TranslatableMarkup('AI Prompt Types'),
  label_singular: new TranslatableMarkup('ai prompt type'),
  label_plural: new TranslatableMarkup('ai prompt types'),
  config_prefix: 'ai_prompt_content_type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => AiPromptTypeListBuilder::class,
    'form' => [
      'add' => AiPromptTypeForm::class,
      'edit' => AiPromptTypeForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'collection' => '/admin/structure/ai-prompt-content-type',
    'add-form' => '/admin/structure/ai-prompt-content-type/add',
    'edit-form' => '/admin/structure/ai-prompt-content-type/{ai_prompt_content_type}',
    'delete-form' => '/admin/structure/ai-prompt-content-type/{ai_prompt_content_type}/delete',
  ],
  admin_permission: 'administer ai_prompt_content_type',
  label_count: [
    'singular' => '@count ai prompt type',
    'plural' => '@count ai prompt types',
  ],
  config_export: [
    'id',
    'label',
    'description',
  ],
)]
final class AiPromptType extends ConfigEntityBase implements AiPromptTypeInterface {

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
