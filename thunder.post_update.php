<?php

/**
 * @file
 * Update functions for the thunder installation profile.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ckeditor5\SmartDefaultSettings;
use Drupal\editor\Entity\Editor;
use Drupal\entity_browser\Entity\EntityBrowser;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\MediaType;
use Drupal\user\Entity\Role;

/**
 * Update to Thunder 7.
 */
function thunder_post_update_0001_upgrade_to_thunder7(array &$sandbox): string {
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
  $moduleInstaller = \Drupal::service('module_installer');
  $moduleInstaller->install([
    'media_library_media_modify',
    'gin_toolbar',
    'ckeditor5',
  ]);

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');
  $updater->executeUpdate('thunder', 'thunder_post_update_0001_upgrade_to_thunder7');

  $permissions = [];
  /** @var \Drupal\entity_browser\Entity\EntityBrowser $entity_browser */
  foreach (EntityBrowser::loadMultiple() as $entity_browser) {
    $permissions[] = 'access ' . $entity_browser->id() . ' entity browser pages';
  }
  foreach (Role::loadMultiple() as $role) {
    foreach ($permissions as $permission) {
      if ($role->hasPermission($permission)) {
        $role->revokePermission($permission);
      }
    }
    $role->save();
  }

  foreach (EntityFormDisplay::loadMultiple() as $entity_form_display) {
    $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_form_display->getTargetEntityTypeId(), $entity_form_display->getTargetBundle());
    foreach ($entity_form_display->getComponents() as $component_name => $component) {
      if (!isset($field_definitions[$component_name])) {
        continue;
      }
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
      $field_definition = $field_definitions[$component_name];
      if ($component['type'] === 'entity_browser_entity_reference' && $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'media') {
        $multiple = $field_definition->getFieldStorageDefinition()->getCardinality() !== 1;
        $component['type'] = 'media_library_media_modify_widget';
        $component['settings'] = [
          'add_button_text' => Drupal::translation()->formatPlural($multiple ? 2 : 1, 'Select @label', 'Select @labels', [
            '@label' => 'media item',
            '@labels' => 'media items',
          ]),
          'check_selected' => $multiple,
          'form_mode' => 'override',
          'no_edit_on_create' => $multiple,
          'multi_edit_on_create' => FALSE,
          'replace_checkbox_by_order_indicator' => $multiple,
        ];
        $entity_form_display->setComponent($component_name, $component);
      }
      $entity_form_display->save();
    }
  }

  $ckEditorMigration = new SmartDefaultSettings(
    \Drupal::service('plugin.manager.ckeditor5.plugin'),
    $updater->logger(),
    \Drupal::service('module_handler'),
    \Drupal::service('current_user'));

  foreach (Editor::loadMultiple() as $editor) {
    $format = $editor->getFilterFormat();
    [$updated_text_editor] = $ckEditorMigration->computeSmartDefaultSettings($editor, $format);
    $updated_text_editor->save();
  }

  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
  $moduleInstaller = \Drupal::service('module_installer');
  $moduleInstaller->uninstall([
    'ckeditor',
    'entity_browser',
    'entity_browser_entity_form',
    'dropzonejs_eb_widget',
  ]);

  /** @var \Drupal\Core\Extension\ThemeInstallerInterface $themeInstaller */
  $themeInstaller = \Drupal::service('theme_installer');
  $themeInstaller->uninstall(['thunder_admin', 'seven']);

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Configure input formats to enable paragraphs split.
 */
function thunder_post_update_0002_enable_paragraphs_split(array &$sandbox): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('thunder', 'thunder_post_update_0002_enable_paragraphs_split');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Enable sticky action buttons for the Gin theme.
 */
function thunder_post_update_0003_enable_sticky_action_buttons(array &$sandbox): string {
  \Drupal::configFactory()->getEditable('gin.settings')
    ->set('sticky_action_buttons', TRUE)
    ->save();

  return t('Sticky action buttons enabled.');
}

/**
 * This update removes blazy and slick integration.
 */
function thunder_post_update_0004_remove_blazy_and_slick(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  // Execute configuration update definitions with logging of success.
  $updater->executeUpdate('thunder', 'thunder_post_update_0004_remove_blazy_and_slick');

  // Output logged messages to related channel of update execution.
  return $updater->logger()->output();
}

/**
 * Update field widget types from 'select2' to 'tagify'.
 */
function thunder_post_update_0005_switch_to_tagify(): string {
  $moduleInstaller = \Drupal::service('module_installer');
  $moduleInstaller->install(['tagify']);

  $storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
  $display_ids = $storage->getQuery()->execute();

  foreach ($storage->loadMultiple($display_ids) as $form_display) {
    $components = $form_display->getComponents();
    foreach ($components as $field_name => $component) {
      if (!empty($component['type']) && $component['type'] === 'select2_entity_reference') {

        // Get field definition and base settings.
        $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions(
          $form_display->getTargetEntityTypeId(),
          $form_display->getTargetBundle()
        );
        $field_definition = $field_definitions[$field_name];

        // Set core autocomplete for uid (author) field.
        if ($field_name === "uid") {
          $plugin_id = 'entity_reference_autocomplete';
        }
        else {
          $plugin_id = 'tagify_entity_reference_autocomplete_widget';
        }
        $widget_manager = \Drupal::service('plugin.manager.field.widget');

        $plugin = $widget_manager->createInstance($plugin_id, [
          'field_definition' => $field_definition,
          'settings' => [],
          'third_party_settings' => [],
        ]);
        $default_settings = $plugin->getSettings();

        $form_display->setComponent($field_name, [
          'type' => $plugin_id,
          'settings' => $default_settings,
          'third_party_settings' => [],
        ] + $component);

        $form_display->save();
      }
    }
  }

  return t('Updated field widget types from "select2" to "tagify".');
}

/**
 * Remove empty media items.
 */
function thunder_post_update_0006_remove_empty_media_items(array &$sandbox): ?TranslatableMarkup {
  if (empty($sandbox['thunder_post_update_0006'])) {
    // Create a list of media types to process. We skip processing media types
    // with custom storage.
    foreach (MediaType::loadMultiple() as $id => $mediaType) {
      $sourceField = $mediaType->getSource()->getSourceFieldDefinition($mediaType);
      if (!$sourceField->getFieldStorageDefinition()->hasCustomStorage()) {
        $sandbox['thunder_post_update_0006']['media_types'][$id] = $id;
      }
    }
    $sandbox['thunder_post_update_0006']['media_types_count'] = count($sandbox['thunder_post_update_0006']['media_types']);
    $sandbox['thunder_post_update_0006']['batch_size'] = Settings::get('entity_update_batch_size');
  }

  $message = NULL;
  if (!empty($sandbox['thunder_post_update_0006']['media_types'])) {
    $mediaType = MediaType::load(reset($sandbox['thunder_post_update_0006']['media_types']));
    $sourceField = $mediaType->getSource()->getSourceFieldDefinition($mediaType);
    $query = \Drupal::entityQuery('media');
    $query->condition('bundle', $mediaType->id());
    $query->condition($sourceField->getName(), '', 'IS NULL');
    $query->accessCheck(FALSE);
    $query->range(0, $sandbox['thunder_post_update_0006']['batch_size']);
    $ids = $query->execute();
    $count = count($ids);
    if (!empty($ids)) {
      $media_entities = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($ids);
      \Drupal::entityTypeManager()->getStorage('media')->delete($media_entities);
    }
    if ($count < $sandbox['thunder_post_update_0006']['batch_size']) {
      // There are no more empty media items to remove of this type.
      unset($sandbox['thunder_post_update_0006']['media_types'][$mediaType->id()]);
    }
    if ($count > 0) {
      $message = t('Removed @count empty @type media items.', ['@count' => $count, '@type' => $mediaType->label()]);
    }
  }

  if (!empty($sandbox['thunder_post_update_0006']['media_types'])) {
    $processed = $sandbox['thunder_post_update_0006']['media_types_count'] - count($sandbox['thunder_post_update_0006']['media_types']);
    $sandbox['#finished'] = $processed / $sandbox['thunder_post_update_0006']['media_types_count'];
  }
  else {
    $sandbox['#finished'] = 1;
  }

  return $message;
}

/**
 * Add "Edited with AI" and "Created with AI" fields to image media.
 */
function thunder_post_update_0007_add_ai_fields_to_image_media(): TranslatableMarkup {
  $field_name = 'field_digital_source_type';

  if (!FieldStorageConfig::loadByName('media', $field_name)) {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'media',
      'type' => 'list_string',
      'cardinality' => 1,
      'translatable' => TRUE,
      'settings' => [
        'allowed_values' => [
          'trainedAlgorithmicMedia' => 'Created with AI',
          'compositeWithTrainedAlgorithmicMedia' => 'Edited with AI',
        ],
      ],
    ])->save();
  }

  if (!FieldConfig::loadByName('media', 'image', $field_name)) {
    try {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'media',
        'bundle' => 'image',
        'label' => 'AI disclosure',
        'required' => FALSE,
        'translatable' => TRUE,
      ])->save();
    }
    catch (\Exception $e) {
      // Resaving displays for the bundle can hit unrelated stale data.
      \Drupal::logger('thunder')->warning('Could not save the "field_digital_source_type" field or resave dependent displays: @message', ['@message' => $e->getMessage()]);
    }
  }

  $form_display = EntityFormDisplay::load('media.image.default');
  if ($form_display && !$form_display->getComponent($field_name)) {
    $form_display->setComponent($field_name, [
      'type' => 'options_select',
      'weight' => 6,
      'region' => 'content',
    ]);

    $group = $form_display->getThirdPartySetting('field_group', 'group_credits');
    if ($group && !in_array($field_name, $group['children'], TRUE)) {
      $group['children'][] = $field_name;
      $form_display->setThirdPartySetting('field_group', 'group_credits', $group);
    }

    try {
      $form_display->save();
    }
    catch (\Exception $e) {
      // Widget placement is cosmetic; unrelated stale data must not abort this.
      \Drupal::logger('thunder')->warning('Could not save the "media.image.default" form display while adding the AI disclosure widget: @message', ['@message' => $e->getMessage()]);
    }
  }

  return t('Added "AI disclosure" field to the Image media type.');
}
