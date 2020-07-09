<?php

namespace Drupal\thunder\Installer\Form;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\thunder\OptionalModulesManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the site configuration form.
 */
class ModuleConfigureForm extends FormBase {

  /**
   * The plugin manager.
   *
   * @var \Drupal\thunder\OptionalModulesManager
   */
  protected $optionalModulesManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = parent::create($container);
    $form->setOptionalModulesManager($container->get('plugin.manager.thunder.optional_modules'));
    $form->setConfigFactory($container->get('config.factory'));
    $form->setModuleHander($container->get('module_handler'));
    return $form;
  }

  /**
   * Set the modules manager.
   *
   * @param \Drupal\thunder\OptionalModulesManager $manager
   *   The manager service.
   */
  protected function setOptionalModulesManager(OptionalModulesManager $manager) {
    $this->optionalModulesManager = $manager;
  }

  /**
   * Set the module handler service.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  protected function setModuleHander(ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
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

    $providers = $this->optionalModulesManager->getModules();

    uasort($providers, ['self', 'sortByLabelElement']);
    uasort($providers, [SortArray::class, 'sortByWeightElement']);

    foreach ($providers as $provider) {
      $should_enable = InstallerKernel::installationAttempted() && $provider['standardlyEnabled'];

      /** @var \Drupal\thunder\Plugin\Thunder\OptionalModule\AbstractOptionalModule $instance */
      $instance = $this->optionalModulesManager->createInstance($provider['id']);

      $form['install_modules_' . $provider['id']] = [
        '#type' => 'checkbox',
        '#title' => $provider['label'],
        '#description' => $provider['description'],
        '#default_value' => $instance->enabled() || $should_enable,
        '#disabled' => $instance->enabled(),
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
        if (!$this->moduleHandler->moduleExists($values['name'])) {
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
        'title' => t('Installing additional modules'),
        'error_message' => t('The installation has encountered an error.'),
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

    /** @var \Drupal\thunder\Plugin\Thunder\OptionalModule\AbstractOptionalModule $instance */
    $instance = $this->optionalModulesManager->createInstance($module);
    $instance->install($form_values, $context);
  }

  /**
   * Sorts a structured array by 'label' key (no # prefix).
   *
   * Callback for uasort().
   *
   * @param array $a
   *   First item for comparison. The compared items should be associative
   *   arrays that optionally include a 'label' key.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  protected static function sortByLabelElement(array $a, array $b) {
    return SortArray::sortByKeyString($a, $b, 'label');
  }

}
