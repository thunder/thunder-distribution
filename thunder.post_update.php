<?php

/**
 * @file
 * Update functions for the thunder installation profile.
 */

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
