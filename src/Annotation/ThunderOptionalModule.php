<?php

namespace Drupal\thunder\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an thunder optional module annotation object.
 *
 * @see hook_entity_browser_widget_info_alter()
 *
 * @Annotation
 */
class ThunderOptionalModule extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the module.
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public $label = '';

  /**
   * A brief description.
   *
   * @var string
   */
  public $description = '';

  /**
   * The weight of the plugin in relation to other plugins.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * Should the module be enabled by default.
   *
   * @var bool
   */
  public $standardlyEnabled = FALSE;

  /**
   * List of module names to enable.
   *
   * @var string[]
   */
  public $modules = [];

  /**
   * List of theme names to enable.
   *
   * @var string[]
   */
  public $themes = [];

}
