<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

use Drupal\Tests\thunder\FunctionalJavascript\ThunderArticleTestTrait;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderJavascriptTestBase;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderParagraphsTestTrait;

/**
 * Tests the paragraph split module integration.
 *
 * @group Thunder
 */
class ParagraphSplitTest extends ThunderJavascriptTestBase {

  use ThunderParagraphsTestTrait;
  use ThunderArticleTestTrait;

  /**
   * Field name for paragraphs in article content.
   *
   * @var string
   */
  protected static string $paragraphsField = 'field_paragraphs';

  /**
   * Selector template for CKEditor instances.
   *
   * To use it, you have to provide a string containing the paragraphs field
   * name and the delta of the paragraph.
   *
   * @var string
   */
  protected static string $selectorTemplate = "textarea[name='%s[%d][subform][field_text][0][value]']";

  /**
   * Test split of paragraph before a selection.
   */
  public function testParagraphSplitBefore(): void {
    $firstParagraphContent = '<p>Content that will be in the first paragraph after the split.</p>';
    $secondParagraphContent = '<p>Content that will be in the second paragraph after the split.</p>';

    $this->drupalGet("node/add/article");
    // Add text paragraph with two elements.
    $this->addTextParagraph(static::$paragraphsField, $firstParagraphContent . $secondParagraphContent);

    // Select second element in editor.
    $ck_editor_id = $this->getCkEditorId($this->getCkEditorCssSelector(0));
    $this->setEditorSelection($ck_editor_id, 'second');
    // Split text paragraph before the current selection.
    $this->clickParagraphSplitButton($ck_editor_id);

    // Validate split results.
    $this->assertCkEditorContent($this->getCkEditorCssSelector(0), $firstParagraphContent);
    $this->assertCkEditorContent($this->getCkEditorCssSelector(1), $secondParagraphContent);
  }

  /**
   * Test if a deleted paragraph leads to data loss.
   */
  public function testParagraphSplitDataLoss(): void {
    $firstParagraphContent = '<p>Content that will be in the first paragraph after the split.</p>';
    $secondParagraphContent = '<p>Content that will be in the second paragraph after the split.</p>';

    $this->drupalGet("node/add/article");

    // Create first paragraph.
    $this->addTextParagraph(static::$paragraphsField, '');

    // Remove the paragraph.
    $driver = $this->getSession()->getDriver();
    $driver->executeScript("document.querySelector('[name=\"field_paragraphs_0_remove\"]').dispatchEvent(new MouseEvent('mousedown'))");
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Create second paragraph.
    $this->addTextParagraph(static::$paragraphsField, $firstParagraphContent . $secondParagraphContent);
    // Select second element in editor.
    $ck_editor_id = $this->getCkEditorId($this->getCkEditorCssSelector(1));
    $this->setEditorSelection($ck_editor_id, 'second');

    // Split text paragraph.
    $this->clickParagraphSplitButton($ck_editor_id);

    // Test if all texts are in the correct paragraph.
    $this->assertCkEditorContent($this->getCkEditorCssSelector(1), $firstParagraphContent);
    $this->assertCkEditorContent($this->getCkEditorCssSelector(2), $secondParagraphContent);
  }

  /**
   * Test if a adding paragraph after split leads to data loss.
   */
  public function testAddParagraphAfterSplitDataLoss(): void {
    $firstParagraphContent = '<p>Content that will be in the first paragraph after the split.</p>';
    $secondParagraphContent = '<p>Content that will be in the second paragraph after the split.</p>';
    $thirdParagraphContent = '<p>Content that will be placed into the first paragraph after split.</p>';

    $this->drupalGet("node/add/article");

    // Create first paragraph.
    $this->addTextParagraph(static::$paragraphsField, $firstParagraphContent . $secondParagraphContent);

    // Select second element in editor.
    $ck_editor_id = $this->getCkEditorId($this->getCkEditorCssSelector(0));
    $this->setEditorSelection($ck_editor_id, 'second');

    // Split text paragraph.
    $this->clickParagraphSplitButton($ck_editor_id);

    $paragraphDelta = $this->getParagraphDelta(static::$paragraphsField, 0);
    $ckEditorCssSelector = "textarea[name='" . static::$paragraphsField . "[{$paragraphDelta}][subform][field_text][0][value]']";

    $this->fillCkEditor(
      $ckEditorCssSelector,
      $thirdParagraphContent
    );

    $ckEditorId = $this->getCkEditorId($ckEditorCssSelector);
    $this->getSession()
      ->getDriver()
      ->executeScript("const editor = Drupal.CKEditor5Instances.get('$ckEditorId'); editor.setData(\"$thirdParagraphContent\"); // window.ed.updateElement(); // window.ed.element.data('editor-value-is-changed', true);");

    $this->addTextParagraph(static::$paragraphsField, '', 'text', 1);

    // Test if all texts are in the correct paragraph.
    $this->assertCkEditorContent($this->getCkEditorCssSelector(0), $thirdParagraphContent);
    $this->assertCkEditorContent($this->getCkEditorCssSelector(2), '');
    $this->assertCkEditorContent($this->getCkEditorCssSelector(1), $secondParagraphContent);
  }

  /**
   * Assert that CKEditor instance contains correct data.
   *
   * @param string $ckEditorCssSelector
   *   CSS selector for CKEditor.
   * @param string $expectedContent
   *   The expected content.
   */
  public function assertCkEditorContent($ckEditorCssSelector, $expectedContent): void {
    $ckEditorId = $this->getCkEditorId($ckEditorCssSelector);
    $ckEditorContent = $this->getSession()
      ->getDriver()
      ->evaluateScript("Drupal.CKEditor5Instances.get('$ckEditorId').getData();");

    static::assertEquals($expectedContent, $ckEditorContent);
  }

  /**
   * Click on split text button for paragraphs text field.
   *
   * @param string $ck_editor_id
   *   Id of CKEditor field in paragraphs.
   */
  protected function clickParagraphSplitButton($ck_editor_id): void {
    $button = $this->assertSession()
      ->waitForElementVisible('xpath', '//textarea[@data-ckeditor5-id="' . $ck_editor_id . '"]/following-sibling::div//button[span[text()="Split Paragraph"]]');
    $this->assertNotEmpty($button);
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Create css selector for paragraph with the given delta.
   *
   * @param int $paragraphDelta
   *   The delta of the paragraph.
   *
   * @return string
   *   Css selector for the paragraph.
   */
  protected function getCkEditorCssSelector($paragraphDelta) {
    return sprintf(static::$selectorTemplate, static::$paragraphsField, $paragraphDelta);
  }

  /**
   * Set selection to beginning of an element containing a given string.
   *
   * @param string $ck_editor_id
   *   Id of CKEditor field in paragraphs.
   * @param string $needle
   *   String contained by element.
   */
  protected function setEditorSelection($ck_editor_id, $needle): void {
    $script = <<<JS
(function (editorId, needle) {
  const editor = Drupal.CKEditor5Instances.get(editorId);
  editor.model.change( writer => {
    const selection = writer.createSelection(editor.model.document.getRoot(), 'in');
    let newPosition;
    for (const range of selection.getRanges()) {
      for (const item of range.getItems()) {
        if (item.data?.includes(needle)) {
          newPosition = writer.createPositionAt(item, 'before');
          break;
        }
      }
    }
    const newRange = writer.createRange( newPosition );
    writer.setSelection( newRange );
    editor.focus()
  });
})('{$ck_editor_id}', '{$needle}')
JS;

    $this->getSession()->getDriver()->executeScript($script);
  }

}
