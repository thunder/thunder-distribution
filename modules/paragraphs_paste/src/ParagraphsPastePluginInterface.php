<?php

namespace Drupal\paragraphs_paste;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for ParagraphsPaste plugins.
 *
 * @see \Drupal\paragraphs_paste\Annotation\ParagraphsPastePlugin
 * @see \Drupal\paragraphs_paste\ParagraphsPastePluginBase
 * @see \Drupal\paragraphs_paste\ParagraphsPastePluginInterface
 * @see \Drupal\paragraphs_paste\ParagraphsPastePluginManager
 * @see plugin_api
 */
interface ParagraphsPastePluginInterface extends PluginInspectionInterface {

  /**
   * Builds an paragraph entity.
   *
   * This method is responsible for transforming arbitrary content, f.e. a link
   * into a paragraph entity.
   *
   * @param string $input
   *   Input string to be parsed into paragraphs entity.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph
   *   An paragraph entity.
   */
  public function build($input);

  /**
   * Returns if the plugin can be used for the provided content.
   *
   * @param string $input
   *   Input string.
   *
   * @return bool
   *   TRUE if the plugin can be used, FALSE otherwise.
   */
  public static function isApplicable($input);

}
