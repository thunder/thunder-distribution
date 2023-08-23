<?php

/**
 * @file
 * Update functions for the thunder installation profile.
 */

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
    /** @var \Drupal\entity_browser\Entity\EntityBrowser $entity_browser */
    foreach ($permissions as $permission) {
      if ($role->hasPermission($permission)) {
        $role->revokePermission($permission);
      }
    }
    $role->save();
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
