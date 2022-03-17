<?php

/**
 * @file
 * Update functions for the thunder installation profile.
 */

use Drupal\user\Entity\Role;

/**
 * Update to Thunder 7.
 */
function thunder_post_update_upgrade_to_thunder7(array &$sandbox): string {
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
  $moduleInstaller = \Drupal::service('module_installer');
  $moduleInstaller->install(['media_library_media_modify', 'gin_toolbar']);

  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  /** @var \Drupal\media_library_media_modify\EntityReferenceOverrideService $entityReferenceOverrideService */
  $entityReferenceOverrideService = \Drupal::service('media_library_media_modify');
  $entityReferenceOverrideService->migrateEntityReferenceField('node', 'field_teaser_media');
  $entityReferenceOverrideService->migrateEntityReferenceField('media', 'field_media_images');
  $entityReferenceOverrideService->migrateEntityReferenceField('paragraph', 'field_image');
  $entityReferenceOverrideService->migrateEntityReferenceField('paragraph', 'field_video');

  $updater->executeUpdate('thunder', 'thunder_update_8323');

  $permissions = [
    'access image_browser entity browser pages',
    'access multiple_image_browser entity browser pages',
    'access video_browser entity browser pages',
  ];
  foreach (['seo', 'editor', 'restricted_editor'] as $role_name) {
    try {
      if ($role = Role::load($role_name)) {
        foreach ($permissions as $permission) {
          $role->revokePermission($permission);
        }
        $role->save();
      }
    }
    catch (\Exception $exception) {
    }
  }

  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
  $moduleInstaller = \Drupal::service('module_installer');
  $moduleInstaller->uninstall([
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
