<?php

/**
 * @file
 * Enables modules and site configuration for a thunder site installation.
 */

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\Dependency;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Form\FormStateInterface;
use Drupal\block\Entity\Block;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function thunder_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  // Add a value as example that one can choose an arbitrary site name.
  $form['site_information']['site_name']['#placeholder'] = t('Thunder');
}

/**
 * Implements hook_install_tasks().
 */
function thunder_install_tasks(&$install_state) {
  $tasks = [];
  if (empty($install_state['config_install_path'])) {
    $tasks['thunder_module_configure_form'] = [
      'display_name' => t('Configure additional modules'),
      'type' => 'form',
      'function' => 'Drupal\thunder\Installer\Form\ModuleConfigureForm',
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
function thunder_module_install(array &$install_state) {
  return $install_state['thunder_install_batch'];
}

/**
 * Finish Thunder installation process.
 *
 * @param array $install_state
 *   The install state.
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function thunder_finish_installation(array &$install_state) {
  \Drupal::service('config.installer')->installOptionalConfig();

  // Assign user 1 the "administrator" role.
  $user = User::load(1);
  $user->roles[] = 'administrator';
  $user->save();
}

/**
 * Implements hook_themes_installed().
 */
function thunder_themes_installed($theme_list) {

  if (in_array('thunder_amp', $theme_list)) {
    // Install AMP module.
    \Drupal::service('module_installer')->install(['amp'], TRUE);

    \Drupal::configFactory()
      ->getEditable('amp.settings')
      ->set('amp_library_process_full_html', TRUE)
      ->save(TRUE);

    // Set AMP theme to thunder_amp,
    // if not set, or is one of the included themes.
    $ampThemeConfig = \Drupal::configFactory()->getEditable('amp.theme');
    $ampTheme = $ampThemeConfig->get('amptheme');
    if (empty($ampTheme) || $ampTheme == 'ampsubtheme_example' || $ampTheme == 'amptheme') {
      $ampThemeConfig->set('amptheme', 'thunder_amp')
        ->save(TRUE);
    }

    // Disable unused blocks.
    /** @var \Drupal\block\Entity\Block[] $blocks */
    $blocks = Block::loadMultiple([
      'thunder_amp_account_menu',
      'thunder_amp_breadcrumbs',
      'thunder_amp_footer',
      'thunder_amp_local_actions',
      'thunder_amp_local_tasks',
      'thunder_amp_main_menu',
      'thunder_amp_messages',
      'thunder_amp_tools',
    ]);
    foreach ($blocks as $block) {
      $block->disable()->save();
    }

  }

  if (in_array('amptheme', $theme_list)) {
    \Drupal::service('module_installer')->install(['amp'], TRUE);
  }
}

/**
 * Implements hook_modules_installed().
 */
function thunder_modules_installed($modules) {
  if (!InstallerKernel::installationAttempted() && !Drupal::isConfigSyncing()) {
    /** @var \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList */
    $moduleExtensionList = \Drupal::service('extension.list.module');
    $thunder_features = array_filter($moduleExtensionList->getList(), function (Extension $module) {
      return $module->info['package'] === 'Thunder Optional';
    });

    foreach ($thunder_features as $id => $extension) {

      $dependencies = array_map(function ($dependency) {
        return Dependency::createFromString($dependency)->getName();
      }, $extension->info['dependencies']);

      if (!in_array($id, $modules) && !empty(array_intersect($modules, $dependencies))) {
        \Drupal::messenger()->addWarning(t('To get the full Thunder experience, we recommend to install the @module module. See all supported optional modules at <a href="/admin/modules/extend-thunder">Thunder Optional modules</a>.', ['@module' => $extension->info['name']]));
      }
    }
  }
}

/**
 * Implements hook_modules_uninstalled().
 */
function thunder_modules_uninstalled($modules) {
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
function thunder_page_attachments(array &$attachments) {

  foreach ($attachments['#attached']['html_head'] as &$html_head) {

    $name = $html_head[1];

    if ($name == 'system_meta_generator') {
      $tag = &$html_head[0];
      $tag['#attributes']['content'] = 'Drupal 8 (Thunder | http://www.thunder.org)';
    }
  }
}

/**
 * Implements hook_library_info_alter().
 */
function thunder_toolbar_alter(&$items) {
  if (!empty($items['admin_toolbar_tools'])) {
    $items['admin_toolbar_tools']['#attached']['library'][] = 'thunder/toolbar.icon';
  }
}

/**
 * Implements hook_field_widget_info_alter().
 */
function thunder_field_widget_info_alter(array &$info) {
  if (!\Drupal::moduleHandler()->moduleExists('content_moderation')) {
    unset($info['thunder_moderation_state_default']);
  }
}

/**
 * Implements hook_field_widget_multivalue_WIDGET_TYPE_form_alter().
 *
 * Removes the cardinality information from the #prefix element of the current
 * selection.
 */
function thunder_field_widget_multivalue_entity_browser_entity_reference_form_alter(array &$elements, FormStateInterface $form_state, array $context) {
  unset($elements['current']['#prefix']);
}
