<?php

namespace Drupal\paragraphs_paste\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ParagraphsPastePlugin annotation object.
 *
 * Plugin Namespace: Plugin\ParagraphsPastePlugin.
 *
 * @see \Drupal\paragraphs_paste\ParagraphsPastePluginManager
 * @see hook_paragraphs_paste_plugin_info_alter()
 * @see plugin_api
 *
 * @Annotation
 */
class ParagraphsPastePlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The display label/name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * Weight of the plugin.
   *
   * @var int
   */
  public $weight;

}
