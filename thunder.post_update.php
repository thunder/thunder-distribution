<?php

/**
 * @file
 * Update functions for the thunder installation profile.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\ckeditor5\SmartDefaultSettings;
use Drupal\editor\Entity\Editor;
use Drupal\entity_browser\Entity\EntityBrowser;
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
    'jquery_ui',
    'jquery_ui_draggable',
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

  /** @var \Drupal\ckeditor5\SmartDefaultSettings $ckEditorMigration */
  $ckEditorMigration = new SmartDefaultSettings(
    \Drupal::service('plugin.manager.ckeditor5.plugin'),
    \Drupal::service('plugin.manager.public_ckeditor4to5upgrade.plugin'),
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
