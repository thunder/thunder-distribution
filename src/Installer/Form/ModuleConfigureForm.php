<?php

namespace Drupal\thunder\Installer\Form;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\Core\Extension\ModuleDependencyMessageTrait;
use Drupal\user\PermissionHandlerInterface;
use Drupal\Component\Utility\Environment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the site configuration form.
 */
class ModuleConfigureForm extends FormBase {

  use ModuleDependencyMessageTrait;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The access manager service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The permission handler service.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->setModuleExtensionList($container->get('extension.list.module'));
    $form->setModuleInstaller($container->get('module_installer'));
    $form->setAccessManager($container->get('access_manager'));
    $form->setCurrentUser($container->get('current_user'));
    $form->setModuleHandler($container->get('module_handler'));
    $form->setPermissionHandler($container->get('user.permissions'));
    $form->setConfigFactory($container->get('config.factory'));
    return $form;
  }

  /**
   * Set the module extension list.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   */
  protected function setModuleExtensionList(ModuleExtensionList $moduleExtensionList) {
    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * Set the modules installer.
   *
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module installer.
   */
  protected function setModuleInstaller(ModuleInstallerInterface $moduleInstaller) {
    $this->moduleInstaller = $moduleInstaller;
  }

  /**
   * Set the access manager.
   *
   * @param \Drupal\Core\Access\AccessManagerInterface $accessManager
   *   The access manager service.
   */
  protected function setAccessManager(AccessManagerInterface $accessManager) {
    $this->accessManager = $accessManager;
  }

  /**
   * Set the current user.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   The current user.
   */
  protected function setCurrentUser(AccountProxyInterface $accountProxy) {
    $this->currentUser = $accountProxy;
  }

  /**
   * Set the module handler service.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  protected function setModuleHandler(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Set the permissions handler service.
   *
   * @param \Drupal\user\PermissionHandlerInterface $handler
   *   The permissions handler service.
   */
  protected function setPermissionHandler(PermissionHandlerInterface $handler) {
    $this->permissionHandler = $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'thunder_module_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This is a list of modules that are supported by Thunder, but not enabled by default.'),
    ];

    $form['install_modules'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $modules = $this->moduleExtensionList->getList();
    $thunder_features = array_filter($modules, function (Extension $module) {
      return $module->info['package'] === 'Thunder Optional' && (!isset($module->info['hidden']) || !$module->info['hidden']);
    });

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

      if ($module->status) {

        // Generate link for module's help page. Assume that if a hook_help()
        // implementation exists then the module provides an overview page,
        // rather than checking to see if the page exists, which is costly.
        if ($this->moduleHandler->moduleExists('help') && in_array($module->getName(), $this->moduleHandler->getImplementations('help'))) {
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
        if ($this->currentUser->hasPermission('administer permissions') && $this->permissionHandler->moduleProvidesPermissions($module->getName())) {
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
          $route_parameters = isset($module->info['configure_parameters']) ? $module->info['configure_parameters'] : [];
          if ($this->accessManager->checkNamedRoute($module->info['configure'], $route_parameters, $this->currentUser)) {
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = [];
    foreach ($form_state->getValue('install_modules') as $module => $values) {
      $extension = $this->moduleExtensionList->get($module);
      if (!$extension->status && $values['enable']) {
        $operations[] = [
          [$this, 'batchOperation'],
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
  public function batchOperation($module, array &$context) {
    Environment::setTimeLimit(0);
    $this->moduleInstaller->install([$module]);
  }

}
