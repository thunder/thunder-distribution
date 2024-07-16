<?php

/**
 * @file
 * Enables modules and site configuration for a thunder site installation.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Transaction\StackItem;
use Drupal\Core\Extension\Dependency;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\thunder\Installer\Form\ModuleConfigureForm;
use Drupal\user\Entity\User;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function thunder_form_install_configure_form_alter(array &$form, FormStateInterface $form_state): void {
  // Add a value as example that one can choose an arbitrary site name.
  $form['site_information']['site_name']['#placeholder'] = t('Thunder');
}

/**
 * Implements hook_install_tasks().
 */
function thunder_install_tasks(array &$install_state): array {
  $tasks = [];
  if (empty($install_state['config_install_path'])) {
    $tasks['_thunder_transaction'] = [];
    $tasks['thunder_module_configure_form'] = [
      'display_name' => t('Configure additional modules'),
      'type' => 'form',
      'function' => ModuleConfigureForm::class,
    ];
    $tasks['thunder_module_install'] = [
      'display_name' => t('Install additional modules'),
      'type' => 'batch',
    ];
  }
  $tasks['thunder_finish_installation'] = [
    'display_name' => t('Finish installation'),
  ];
  return $tasks;
}

/**
 * Installs the thunder modules in a batch.
 *
 * @param array $install_state
 *   The install state.
 *
 * @return array
 *   A batch array to execute.
 */
function thunder_module_install(array &$install_state): array {
  return $install_state['thunder_install_batch'] ?? [];
}

/**
 * Finish Thunder installation process.
 *
 * @param array $install_state
 *   The install state.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function thunder_finish_installation(array &$install_state): void {
  // Assign user 1 the "administrator" role.
  $user = User::load(1);
  $user->addRole('administrator');
  $user->save();
}

/**
 * Implements hook_modules_installed().
 */
function thunder_modules_installed(array $modules): void {
  if (!InstallerKernel::installationAttempted() && !Drupal::isConfigSyncing()) {
    /** @var \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList */
    $moduleExtensionList = \Drupal::service('extension.list.module');
    $thunder_features = array_filter($moduleExtensionList->getList(), fn(Extension $module): bool => $module->info['package'] === 'Thunder Optional');

    foreach ($thunder_features as $id => $extension) {

      $dependencies = array_map(fn($dependency): string => Dependency::createFromString($dependency)->getName(), $extension->info['dependencies']);

      if (!in_array($id, $modules) && !empty(array_intersect($modules, $dependencies))) {
        \Drupal::messenger()->addWarning(t('To get the full Thunder experience, we recommend to install the @module module. See all supported optional modules at <a href="/admin/modules/extend-thunder">Thunder Optional modules</a>.', ['@module' => $extension->info['name']]));
      }
    }
  }
}

/**
 * Implements hook_preprocess_html().
 */
function thunder_preprocess_html(array &$variables): void {
  if (!InstallerKernel::installationAttempted() && \Drupal::currentUser()->hasPermission('access toolbar')) {
    $variables['attributes']['class'][] = 'toolbar-icon-thunder';
  }
}

/**
 * Implements template_preprocess_status_report().
 */
function thunder_preprocess_status_report_general_info(array &$variables): void {
  if (!empty($thunder_version = \Drupal::service('extension.list.module')->get('thunder')->info['version'])) {
    $variables['drupal']['value'] .= ' (Thunder ' . $thunder_version . ')';
  }
}

/**
 * Implements hook_modules_uninstalled().
 */
function thunder_modules_uninstalled(array $modules): void {
  // Import the content view if it was deleted during module uninstalling.
  // This could happen if content_lock was uninstalled and the content view
  // contained content_lock fields at that time.
  if (in_array('content_lock', $modules, TRUE)) {
    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');
    $found_routes = $route_provider->getRoutesByPattern('admin/content');
    $view_found = FALSE;
    foreach ($found_routes->getIterator() as $route) {
      if (!empty($route->getDefault('view_id'))) {
        $view_found = TRUE;
        break;
      }
    }
    if (!$view_found) {
      $config_service = \Drupal::service('config_update.config_update');
      $config_service->import('view', 'content');
    }
  }
}

/**
 * Implements hook_page_attachments().
 */
function thunder_page_attachments(array &$attachments): void {

  foreach ($attachments['#attached']['html_head'] as &$html_head) {

    $name = $html_head[1];

    if ($name == 'system_meta_generator') {
      $tag = &$html_head[0];
      $tag['#attributes']['content'] = 'Drupal 10 (Thunder | https://www.thunder.org)';
    }
  }
}

/**
 * Implements hook_field_widget_info_alter().
 */
function thunder_field_widget_info_alter(array &$info): void {
  if (!\Drupal::moduleHandler()->moduleExists('content_moderation')) {
    unset($info['thunder_moderation_state_default']);
  }
}

/**
 * Implements hook_action_info_alter().
 */
function thunder_action_info_alter(array &$definitions): void {
  foreach ($definitions as &$definition) {
    if ($definition['id'] === 'entity:edit_action') {
      $definition['action_label'] = t('Edit');
    }
  }
}

/**
 * Implements hook_gin_content_form_routes().
 *
 * Revisit after https://www.drupal.org/i/3281343 et al are merged.
 */
function thunder_gin_content_form_routes(): array {
  // Do not use gin content edit form layout in ajax context (overlays).
  if (\Drupal::request()->isXmlHttpRequest()) {
    return [];
  }
  $routes = [
    'entity.taxonomy_term.edit_form',
    'entity.taxonomy_term.add_form',
    'entity.media.add_form',
  ];
  if (\Drupal::config('media.settings')->get('standalone_url')) {
    $routes[] = 'entity.media.edit_form';
  }
  else {
    $routes[] = 'entity.media.canonical';
  }
  return $routes;
}

/**
 * Implements hook_media_source_info_alter().
 */
function thunder_media_source_info_alter(array &$sources): void {
  if ($sources['oembed:video']) {
    $sources['oembed:video']['providers'][] = 'TikTok';
  }
}

/**
 * Works around bug caused by Drupal's transaction handling.
 *
 * @param array $install_state
 *   The install state.
 *
 * @todo Remove once https://www.drupal.org/project/drupal/issues/3405976 is
 *   fixed.
 */
function _thunder_transaction(array &$install_state): void {
  $manager = Database::getConnection()->transactionManager();
  $reflection = new \ReflectionClass($manager);
  if (!$reflection->hasMethod('stack')) {
    return;
  }

  $stack = $reflection->getMethod('stack')->invoke($manager);
  if (!is_array($stack)) {
    return;
  }

  foreach (array_reverse($stack) as $id => $stackItem) {
    if ($stackItem instanceof StackItem) {
      $manager->unpile($stackItem->name, $id);
    }
  }
}
