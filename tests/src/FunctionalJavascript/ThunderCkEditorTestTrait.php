<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Trait with helper function to interact with a CkEditor.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderCkEditorTestTrait {

  /**
   * Get CKEditor id from css selector.
   *
   * @param string $ckEditorCssSelector
   *   CSS selector for CKEditor.
   *
   * @return string
   *   CKEditor ID.
   */
  protected function getCkEditorId(string $ckEditorCssSelector) {
    $ckEditor = $this->getSession()->getPage()->find(
      'css',
      $ckEditorCssSelector
    );

    return $ckEditor->getAttribute('data-ckeditor5-id');
  }

  /**
   * Fill CKEditor field.
   *
   * @param string $ckEditorCssSelector
   *   CSS selector for CKEditor.
   * @param string $text
   *   Text that will be filled into CKEditor.
   */
  public function fillCkEditor(string $ckEditorCssSelector, string $text): void {
    $ckEditorId = $this->getCkEditorId($ckEditorCssSelector);

    $this->getSession()
      ->getDriver()
      ->executeScript("Drupal.CKEditor5Instances.get(\"$ckEditorId\").setData(\"$text\");");
  }

}
