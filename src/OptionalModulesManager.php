<?php

namespace Drupal\thunder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an optional modules plugin manager.
 */
class OptionalModulesManager extends DefaultPluginManager {

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The theme extension list service.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * Constructs a OptionalModulesManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $themeExtensionList
   *   The theme extension list service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ModuleExtensionList $moduleExtensionList, ThemeExtensionList $themeExtensionList) {
    parent::__construct(
      'Plugin/Thunder/OptionalModule',
      $namespaces,
      $module_handler,
      'Drupal\thunder\Plugin\Thunder\OptionalModule\AbstractOptionalModule',
      'Drupal\thunder\Annotation\ThunderOptionalModule'
    );
    $this->alterInfo('thunder_optional_module_info');
    $this->setCacheBackend($cache_backend, 'thunder_optional_module_plugins');
    $this->moduleExtensionList = $moduleExtensionList;
    $this->themeExtensionList = $themeExtensionList;
  }

  /**
   * Get all available modules.
   *
   * @return array[]
   *   Array of module definitions.
   */
  public function getModules() {
    return array_filter($this->getDefinitions(), function ($definition) {
      $available = TRUE;

      foreach ($definition['modules'] as $module) {
        $available = $available && $this->moduleExtensionList->exists($module);
      }
      foreach ($definition['themes'] as $theme) {
        $available = $available && $this->themeExtensionList->exists($theme);
      }
      return $available;
    });
  }

}
