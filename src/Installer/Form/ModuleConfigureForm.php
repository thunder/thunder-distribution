<?php

namespace Drupal\thunder\Installer\Form;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\InstallerKernel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the site configuration form.
 */
class ModuleConfigureForm extends FormBase {

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->setModuleExtensionList($container->get('extension.list.module'));
    $form->setModuleInstaller($container->get('module_installer'));
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
      '#markup' => $this->t('Keep calm. You can install all the modules later, too.'),
    ];

    $form['install_modules'] = [
      '#type' => 'container',
    ];

    $thunder_features = array_filter($this->moduleExtensionList->getList(), function (Extension $module) {
      return $module->info['package'] === 'Thunder Optional';
    });

    foreach ($thunder_features as $id => $extension) {
      $form['install_modules_' . $id] = [
        '#type' => 'checkbox',
        '#title' => $extension->info['name'],
        '#description' => $extension->info['description'],
        '#default_value' => $extension->status,
        '#disabled' => $extension->status,
      ];
    }
    $form['#title'] = $this->t('Install & configure modules');

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $installModules = [];

    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'install_modules') !== FALSE && $value) {
        preg_match('/install_modules_(?P<name>\w+)/', $key, $values);

        $extension = $this->moduleExtensionList->get($values['name']);
        if (!$extension->status) {
          $installModules[] = $values['name'];
        }
      }
    }

    if ($installModules) {
      $operations = [];
      foreach ($installModules as $module) {
        $operations[] = [
          [$this, 'batchOperation'],
          [$module, $form_state->getValues()],
        ];
      }

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
   * @param array $form_values
   *   Submitted form values.
   * @param array $context
   *   The batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function batchOperation($module, array $form_values, array &$context) {
    set_time_limit(0);
    $this->moduleInstaller->install([$module]);
  }

}
