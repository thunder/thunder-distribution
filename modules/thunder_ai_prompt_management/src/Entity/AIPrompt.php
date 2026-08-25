<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Form\RevisionDeleteForm;
use Drupal\Core\Entity\Form\RevisionRevertForm;
use Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\thunder_ai_prompt_management\AIPromptAccessControlHandler;
use Drupal\thunder_ai_prompt_management\AIPromptInterface;
use Drupal\thunder_ai_prompt_management\AIPromptListBuilder;
use Drupal\thunder_ai_prompt_management\Form\AIPromptForm;
use Drupal\thunder_ai_prompt_management\Routing\AIPromptHtmlRouteProvider;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the ai prompt entity class.
 */
#[ContentEntityType(
  id: 'ai_prompt_content',
  label: new TranslatableMarkup('AI Prompt'),
  label_collection: new TranslatableMarkup('AI Prompts'),
  label_singular: new TranslatableMarkup('ai prompt'),
  label_plural: new TranslatableMarkup('ai prompts'),
  entity_keys: [
    'id' => 'id',
    'revision' => 'revision_id',
    'label' => 'label',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => AIPromptListBuilder::class,
    'access' => AIPromptAccessControlHandler::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'add' => AIPromptForm::class,
      'edit' => AIPromptForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
      'revision-delete' => RevisionDeleteForm::class,
      'revision-revert' => RevisionRevertForm::class,
    ],
    'route_provider' => [
      'html' => AIPromptHtmlRouteProvider::class,
      'revision' => RevisionHtmlRouteProvider::class,
    ],
  ],
  links: [
    'collection' => '/admin/content/prompts',
    'add-form' => '/admin/content/prompts/add',
    'canonical' => '/admin/content/prompts/{ai_prompt_content}',
    'edit-form' => '/admin/content/prompts/{ai_prompt_content}',
    'delete-form' => '/admin/content/prompts/{ai_prompt_content}/delete',
    'delete-multiple-form' => '/admin/content/prompts/delete-multiple',
    'revision' => '/admin/content/prompts/{ai_prompt_content}/revision/{ai_prompt_content_revision}/view',
    'revision-delete-form' => '/admin/content/prompts/{ai_prompt_content}/revision/{ai_prompt_content_revision}/delete',
    'revision-revert-form' => '/admin/content/prompts/{ai_prompt_content}/revision/{ai_prompt_content_revision}/revert',
    'version-history' => '/admin/content/prompts/{ai_prompt_content}/revisions',
  ],
  admin_permission: 'administer ai_prompt_content',
  collection_permission: 'view ai_prompt_content',
  base_table: 'ai_prompt_content',
  revision_table: 'ai_prompt_content_revision',
  show_revision_ui: TRUE,
  label_count: [
    'singular' => '@count ai prompts',
    'plural' => '@count ai prompts',
  ],
  revision_metadata_keys: [
    'revision_user' => 'revision_uid',
    'revision_created' => 'revision_timestamp',
    'revision_log_message' => 'revision_log',
  ],
)]
class AIPrompt extends EditorialContentEntityBase implements AIPromptInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Published'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Published')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 100,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the ai prompt was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the ai prompt was last edited.'));

    $fields['prompt'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Prompt'))
      ->setDescription(t('The prompt.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -5,
        'rows' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['ai_task'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('AI Task'))
      ->setDescription(t('The ai task this prompt is used for.'))
      ->setSetting('target_type', 'ai_task')
      ->setCardinality(1)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_context'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity context'))
      ->setDescription(t('The entity types and bundles this prompt can be used for.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_context',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['model'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Model'))
      ->setDescription(t('The AI provider model this prompt is intended for.'))
      ->setSetting('max_length', 255)
      ->setRevisionable(TRUE)
      ->setRequired(TRUE);

    return $fields;
  }

}
