<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\Component\Utility\Html;

/**
 * Trait for handling of Paragraph related test actions.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderParagraphsTestTrait {

  use ThunderJavascriptTrait;
  use ThunderMediaTestTrait;
  use ThunderCkEditorTestTrait;

  /**
   * The paragraphs in the quick links.
   *
   * @var int[]
   */
  protected array $thunderQuickLinksAddParagraphTypes = [
    'text' => 1,
    'image' => 2,
    'gallery' => 3,
  ];

  /**
   * Get the paragraphs in the quick links.
   *
   * @return int[]
   *   The paragraphs in the quick links.
   */
  public function getThunderQuickLinksAddParagraphTypes(): array {
    return $this->thunderQuickLinksAddParagraphTypes;
  }

  /**
   * Set the paragraphs in the quick links.
   *
   * @param array $types
   *   The paragraphs in the quick links.
   */
  public function setThunderQuickLinksAddParagraphTypes(array $types): self {
    $this->thunderQuickLinksAddParagraphTypes = $types;
    return $this;
  }

  /**
   * Get number of paragraphs for defined field on current page.
   *
   * @param string $fieldName
   *   Paragraph field name.
   *
   * @return int
   *   Returns number of paragraphs.
   */
  protected function getNumberOfParagraphs(string $fieldName): int {
    $paragraphRows = $this->getParagraphItems($fieldName);

    return count($paragraphRows);
  }

  /**
   * Get paragraph items.
   *
   * @param string $fieldName
   *   Paragraph field name.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The paragraph node element.
   */
  protected function getParagraphItems(string $fieldName) {
    $fieldNamePart = HTML::cleanCssIdentifier($fieldName);

    return $this->xpath("//*[@id=\"edit-{$fieldNamePart}-wrapper\"]//table[starts-with(@id, \"{$fieldNamePart}-values\")]/tbody/tr[contains(@class, \"draggable\")]//div[number(substring-after(@data-drupal-selector, \"edit-{$fieldNamePart}-\")) >= 0]");
  }

  /**
   * Add paragraph for field with defined paragraph type.
   *
   * This uses paragraphs modal widget.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $type
   *   Type of the paragraph.
   * @param int|null $position
   *   Position of the paragraph (default: null).
   *
   * @return int
   *   Returns index for added paragraph.
   *
   * @throws \Exception
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  public function addParagraph(string $fieldName, string $type, ?int $position = NULL): int {
    $numberOfParagraphs = $this->getNumberOfParagraphs($fieldName);

    $types = $this->getThunderQuickLinksAddParagraphTypes();
    $index = $types[$type] ?? count($types) + 1;

    $fieldSelector = HTML::cleanCssIdentifier($fieldName);

    if ($position === NULL || $position > $numberOfParagraphs) {
      $position = $numberOfParagraphs;
      $addButtonCssSelector = "#edit-{$fieldSelector}-wrapper table > tbody > tr:last-child li:nth-child({$index}) button.paragraphs-features__add-in-between__button";
    }
    else {
      $addButtonPosition = $position * 2 + 1;
      $addButtonCssSelector = "#edit-{$fieldSelector}-wrapper table > tbody > tr:nth-child({$addButtonPosition}) li:nth-child({$index}) button.paragraphs-features__add-in-between__button";
    }

    $this->clickCssSelector($addButtonCssSelector);
    if ($index > 3) {
      $this->getSession()->getDriver()->click("//div[contains(@class, \"ui-dialog-content\")]/*[contains(@class, \"paragraphs-add-dialog-list\")]//*[@name=\"{$fieldName}_{$type}_add_more\"]");
      $this->assertWaitOnAjaxRequest();
    }
    $this->assertNotEmpty($this->assertSession()->waitForElementVisible('css', "#edit-{$fieldSelector}-wrapper table > tbody > tr:nth-child(" . (($numberOfParagraphs + 1) * 2 + 1) . ")"));

    // Test if we have one more paragraph now.
    $this->assertEquals(($numberOfParagraphs + 1), $this->getNumberOfParagraphs($fieldName));

    return $this->getParagraphDelta($fieldName, $position);
  }

  /**
   * Get the delta of a paragraph item for a given filed on a specific position.
   *
   * @param string $fieldName
   *   Field name.
   * @param int $position
   *   The Position of the paragraph item.
   *
   * @return int
   *   The delta of the paragraph
   *
   * @throws \Exception
   */
  public function getParagraphDelta(string $fieldName, int $position) {
    $fieldSelector = HTML::cleanCssIdentifier($fieldName);

    // Retrieve new paragraphs delta from id attribute of the item.
    $paragraphItem = $this->getParagraphItems($fieldName)[$position];
    $itemId = $paragraphItem->getAttribute('id');
    preg_match("/^edit-{$fieldSelector}-(\d+)--/", $itemId, $matches);

    if (!isset($matches[1])) {
      throw new \Exception('No new paragraph is found');
    }

    return (int) $matches[1];
  }

  /**
   * Add Image paragraph.
   *
   * @param string $fieldName
   *   Field name.
   * @param array $media
   *   List of media identifiers.
   * @param int $position
   *   Position of the paragraph.
   */
  public function addImageParagraph(string $fieldName, array $media, $position = NULL): void {
    $paragraphIndex = $this->addParagraph($fieldName, 'image', $position);

    $this->selectMedia("{$fieldName}_{$paragraphIndex}_subform_field_image", $media);
  }

  /**
   * Add Video paragraph.
   *
   * @param string $fieldName
   *   Field name.
   * @param array $media
   *   List of media identifiers.
   * @param int $position
   *   Position of the paragraph.
   */
  public function addVideoParagraph(string $fieldName, array $media, $position = NULL): void {
    $paragraphIndex = $this->addParagraph($fieldName, 'video', $position);

    $this->selectMedia("{$fieldName}_{$paragraphIndex}_subform_field_video", $media);
  }

  /**
   * Add Gallery paragraph.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $name
   *   Name of the gallery.
   * @param array $media
   *   List of media identifiers.
   * @param int $position
   *   Position of the paragraph.
   */
  public function addGalleryParagraph(string $fieldName, string $name, array $media, $position = NULL): void {
    $paragraphIndex = $this->addParagraph($fieldName, 'gallery', $position);

    $this->createGallery($name, "{$fieldName}_{$paragraphIndex}_subform_field_media", $media);
  }

  /**
   * Adding text type paragraphs.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $text
   *   Text for paragraph.
   * @param string $type
   *   Type of text paragraph.
   * @param int $position
   *   Position of the paragraph.
   */
  public function addTextParagraph(string $fieldName, string $text, string $type = 'text', $position = NULL): void {
    $paragraphIndex = $this->addParagraph($fieldName, $type, $position);

    if (!empty($text)) {
      $this->fillCkEditor(
        "textarea[name='{$fieldName}[{$paragraphIndex}][subform][field_text][0][value]']",
        $text
      );
    }
  }

  /**
   * Create Twitter, Instagram or PinterestParagraph.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $socialUrl
   *   Url to tweet, instagram or pinterest.
   * @param string $type
   *   Type of paragraph (twitter|instagram|pinterest).
   * @param int $position
   *   Position of the paragraph.
   */
  public function addSocialParagraph(string $fieldName, string $socialUrl, string $type, $position = NULL): void {
    $paragraphIndex = $this->addParagraph($fieldName, $type, $position);

    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    if ($page->hasField("{$fieldName}[{$paragraphIndex}][subform][field_media][0][inline_entity_form][field_url][0][value]")) {
      $page->fillField("{$fieldName}[{$paragraphIndex}][subform][field_media][0][inline_entity_form][field_url][0][value]", $socialUrl);
    }
    elseif ($page->hasField("{$fieldName}[{$paragraphIndex}][subform][field_media][0][inline_entity_form][field_url][0][uri]")) {
      $page->fillField("{$fieldName}[{$paragraphIndex}][subform][field_media][0][inline_entity_form][field_url][0][uri]", $socialUrl);
    }
  }

  /**
   * Add link paragraph.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $urlText
   *   Text that will be displayed for link.
   * @param string $url
   *   Link url.
   * @param int $position
   *   Position of the paragraph.
   */
  public function addLinkParagraph(string $fieldName, string $urlText, string $url, $position = NULL): void {
    $paragraphIndex = $this->addParagraph($fieldName, 'link', $position);

    /** @var \Behat\Mink\Element\DocumentElement $page */
    $page = $this->getSession()->getPage();

    $page->fillField("{$fieldName}[{$paragraphIndex}][subform][field_link][0][title]", $urlText);
    $page->fillField("{$fieldName}[{$paragraphIndex}][subform][field_link][0][uri]", $url);
  }

  /**
   * Click button for editing of paragraph.
   *
   * @param string $paragraphsFieldName
   *   Field name in content type used to paragraphs.
   * @param int $index
   *   Index of paragraph to be edited, starts from 0.
   */
  public function editParagraph(string $paragraphsFieldName, int $index): void {
    $editButtonName = "{$paragraphsFieldName}_{$index}_edit";

    $this->scrollElementInView("[name=\"{$editButtonName}\"]");
    $page = $this->getSession()->getPage();
    $page->pressButton($editButtonName);
    $this->assertWaitOnAjaxRequest();
  }

}
