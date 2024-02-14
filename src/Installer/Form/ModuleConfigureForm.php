<?php

namespace Drupal\thunder\Installer\Form;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionLifecycle;
use Drupal\Core\Extension\ModuleDependencyMessageTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Url;

/**
 * Provides the site configuration form.
 */
class ModuleConfigureForm extends FormBase {

  use ModuleDependencyMessageTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'thunder_module_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This is a list of modules that are supported by Thunder, but not enabled by default.'),
    ];

    $form['install_modules'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $modules = \Drupal::service('extension.list.module')->getList();
    $thunder_features = array_filter($modules, fn(Extension $module): bool => $module->info['package'] === 'Thunder Optional' && (!isset($module->info['hidden']) || !$module->info['hidden']));

    foreach ($thunder_features as $id => $module) {

      $form['install_modules'][$id] = [
        '#type' => 'container',
      ];

      $form['install_modules'][$id]['enable'] = [
        '#type' => 'checkbox',
        '#title' => $module->info['name'],
        '#default_value' => $module->status,
        '#disabled' => $module->status,
      ];

      $form['install_modules'][$id]['info'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['module-info']],
      ];

      $form['install_modules'][$id]['info']['description'] = [
        '#markup' => '<span class="text module-description">' . $module->info['description'] . '</span>',
      ];

      $requires = [];
      // If this module requires other modules, add them to the array.
      /** @var \Drupal\Core\Extension\Dependency $dependency_object */
      foreach ($module->requires as $dependency => $dependency_object) {
        // @todo Add logic for not displaying hidden modules in
        //   https://drupal.org/node/3117829.
        if ($incompatible = $this->checkDependencyMessage($modules, $dependency, $dependency_object)) {
          $requires[$dependency] = $incompatible;
          $form['install_modules'][$id]['enable']['#disabled'] = TRUE;
          continue;
        }

        $name = $modules[$dependency]->info['name'];
        $requires[$dependency] = $modules[$dependency]->status ? $this->t('@module', ['@module' => $name]) : $this->t('@module (<span class="admin-disabled">disabled</span>)', ['@module' => $name]);
      }

      $form['install_modules'][$id]['info']['requires'] = [
        '#prefix' => '<div class="admin-requirements">Requires: ',
        '#suffix' => '</div>',
        '#theme' => 'item_list',
        '#items' => $requires,
        '#context' => ['list_style' => 'comma-list'],
      ];

      $form['install_modules'][$id]['info']['lifecycle'] = [
        '#prefix' => '<div class="admin-requirements">',
        '#suffix' => '</div>',
        '#type' => 'item',
        '#markup' => $this->t('Lifecycle status: @lifecycle', ['@lifecycle' => $module->info[ExtensionLifecycle::LIFECYCLE_IDENTIFIER]]),
      ];

      if ($module->status) {

        // Generate link for module's help page. Assume that if a hook_help()
        // implementation exists then the module provides an overview page,
        // rather than checking to see if the page exists, which is costly.
        if (\Drupal::moduleHandler()->moduleExists('help') && \Drupal::moduleHandler()->hasImplementations('help', $module->getName())) {
          $form['install_modules'][$id]['info']['links']['help'] = [
            '#type' => 'link',
            '#title' => $this->t('Help'),
            '#url' => Url::fromRoute('help.page', ['name' => $module->getName()]),
            '#options' => [
              'attributes' => [
                'class' => ['module-link', 'module-link-help'],
                'title' => $this->t('Help'),
              ],
            ],
          ];
        }

        // Generate link for module's permission, if the user has access to it.
        if ($this->currentUser()->hasPermission('administer permissions') && \Drupal::service('user.permissions')->moduleProvidesPermissions($module->getName())) {
          $form['install_modules'][$id]['info']['links']['permissions'] = [
            '#type' => 'link',
            '#title' => $this->t('Permissions'),
            '#url' => Url::fromRoute('user.admin_permissions'),
            '#options' => [
              'fragment' => 'module-' . $module->getName(),
              'attributes' => [
                'class' => ['module-link', 'module-link-permissions'],
                'title' => $this->t('Configure permissions'),
              ],
            ],
          ];
        }

        // Generate link for module's configuration page, if it has one.
        if (isset($module->info['configure'])) {
          $route_parameters = $module->info['configure_parameters'] ?? [];
          if (\Drupal::service('access_manager')->checkNamedRoute($module->info['configure'], $route_parameters, $this->currentUser())) {
            $form['install_modules'][$id]['info']['links']['configure'] = [
              '#type' => 'link',
              '#title' => $this->t('Configure <span class="visually-hidden">the @module module</span>', ['@module' => $module->info['name']]),
              '#url' => Url::fromRoute($module->info['configure'], $route_parameters),
              '#options' => [
                'attributes' => [
                  'class' => ['module-link', 'module-link-configure'],
                ],
              ],
            ];
          }
        }
      }
    }

    $form['#title'] = $this->t('Install & configure modules');

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'thunder/module.configure.form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $operations = [];
    foreach ($form_state->getValue('install_modules') as $module => $values) {
      $extension = \Drupal::service('extension.list.module')->get($module);
      if (!$extension->status && $values['enable']) {
        $operations[] = [
          [__CLASS__, 'batchOperation'],
          [$module],
        ];
      }
    }

    if ($operations) {
      $batch = [
        'operations' => $operations,
        'title' => $this->t('Installing additional modules'),
        'error_message' => $this->t('The installation has encountered an error.'),
      ];

      if (InstallerKernel::installationAttempted()) {
        $buildInfo = $form_state->getBuildInfo();
        $buildInfo['args'][0]['thunder_install_batch'] = $batch;
        $form_state->setBuildInfo($buildInfo);
      }
      else {
        batch_set($batch);
      }
    }
  }

  /**
   * Batch operation callback.
   *
   * @param string $module
   *   Name of the module.
   * @param array $context
   *   The batch context.
   *
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  public static function batchOperation(string $module, array &$context): void {
    Environment::setTimeLimit(0);
    \Drupal::service('module_installer')->install([$module]);
  }

}
