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
    // Since CKEditor requires some time to initialize, we are going to wait for
    // CKEditor instance to be ready before we continue and return ID.
    $this->getSession()->wait(10000, "(waitForCk = CKEDITOR.instances[jQuery(\"{$ckEditorCssSelector}\").attr('id')]) && waitForCk.instanceReady");

    $ckEditor = $this->getSession()->getPage()->find(
      'css',
      $ckEditorCssSelector
    );

    return $ckEditor->getAttribute('id');
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
      ->executeScript("CKEDITOR.instances[\"$ckEditorId\"].insertHtml(\"$text\");");
  }

  /**
   * Select CKEditor element.
   *
   * @param string $ckEditorCssSelector
   *   CSS selector for CKEditor.
   * @param int $childIndex
   *   The child index under the node.
   */
  public function selectCkEditorElement(string $ckEditorCssSelector, int $childIndex): void {
    $ckEditorId = $this->getCkEditorId($ckEditorCssSelector);

    $this->getSession()
      ->getDriver()
      ->executeScript("let selection = CKEDITOR.instances[\"$ckEditorId\"].getSelection(); selection.selectElement(selection.root.getChild($childIndex)); var ranges = selection.getRanges(); ranges[0].setEndBefore(ranges[0].getBoundaryNodes().endNode); selection.selectRanges(ranges);");
  }

  /**
   * Assert that CKEditor instance contains correct data.
   *
   * @param string $ckEditorCssSelector
   *   CSS selector for CKEditor.
   * @param string $expectedContent
   *   The expected content.
   */
  public function assertCkEditorContent(string $ckEditorCssSelector, string $expectedContent): void {
    $ckEditorId = $this->getCkEditorId($ckEditorCssSelector);
    $ckEditorContent = $this->getSession()
      ->getDriver()
      ->evaluateScript("return CKEDITOR.instances[\"$ckEditorId\"].getData();");

    static::assertEquals($expectedContent, $ckEditorContent);
  }

}
