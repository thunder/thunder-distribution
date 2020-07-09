<?php

namespace Drupal\thunder\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity browser widget annotation object.
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
   * The human-readable name of the widget.
   *
   * @var \Drupal\Core\Annotation\Translation
   * @ingroup plugin_translatable
   */
  public $label = '';

  public $description = '';

  /**
   * The weight of the plugin in relation to other plugins.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * @var bool
   */
  public $standardlyEnabled = FALSE;

  /**
   * @var string[]
   */
  public $modules = [];

  /**
   * @var string[]
   */
  public $themes = [];

}
