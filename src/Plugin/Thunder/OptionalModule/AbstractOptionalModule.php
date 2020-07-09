<?php

namespace Drupal\thunder\Plugin\Thunder\OptionalModule;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Extension\ThemeInstallerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AbstractOptionalModule.
 */
abstract class AbstractOptionalModule extends PluginBase implements ContainerFactoryPluginInterface {

  /**
   * The module installer service.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

  /**
   * The theme installer service.
   *
   * @var \Drupal\Core\Extension\ThemeInstallerInterface
   */
  protected $themeInstaller;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs display plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   The module installer service.
   * @param \Drupal\Core\Extension\ThemeInstallerInterface $themeInstaller
   *   The theme installer service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleInstallerInterface $moduleInstaller, ThemeInstallerInterface $themeInstaller, ModuleHandlerInterface $moduleHandler, ThemeHandlerInterface $themeHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleInstaller = $moduleInstaller;
    $this->themeInstaller = $themeInstaller;

    $this->moduleHandler = $moduleHandler;
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_installer'),
      $container->get('theme_installer'),
      $container->get('module_handler'),
      $container->get('theme_handler'),
    );
  }

  /**
   * Module install instructions.
   *
   * @param array $formValues
   *   Array of submitted form values.
   * @param array $context
   *   The context for the batch operation.
   *
   * @throws \Drupal\Core\Extension\ExtensionNameLengthException
   * @throws \Drupal\Core\Extension\MissingDependencyException
   */
  public function install(array $formValues, array &$context) {
    $context['results'][] = $this->pluginDefinition['id'];

    $this->moduleInstaller->install($this->pluginDefinition['modules'], TRUE);
    $this->themeInstaller->install($this->pluginDefinition['themes'], TRUE);

    $context['message'] = t('Installed %module_name feature.', ['%module_name' => $this->pluginDefinition['label']]);
  }

  /**
   * Indicates if a feature is enabeld.
   *
   * When all modules and themes of a feature are enabled we assume that is was
   * installed from the optional thunder modules form.
   *
   * @return bool
   *   TRUE if all modules and themes are enabled, otherwise FALSE.
   */
  public function enabled() {
    $enabled = TRUE;
    foreach ($this->pluginDefinition['modules'] as $module) {
      $enabled = $enabled && $this->moduleHandler->moduleExists($module);
    }
    foreach ($this->pluginDefinition['themes'] as $theme) {
      $enabled = $enabled && $this->themeHandler->themeExists($theme);
    }
    return $enabled;
  }

}
