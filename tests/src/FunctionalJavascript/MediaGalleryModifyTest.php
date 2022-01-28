<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\SortableTestTrait;

/**
 * Tests the Gallery media modification.
 *
 * @group Thunder
 */
class MediaGalleryModifyTest extends ThunderJavascriptTestBase {

  use ThunderEntityBrowserTestTrait;
  use ThunderParagraphsTestTrait;
  use SortableTestTrait;

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  protected function sortableUpdate($item, $from, $to = NULL): void {
    [$container] = explode(' ', $item, 2);
    $js = <<<END
if (typeof Drupal.entityBrowserEntityReference === 'object') {
  Drupal.entityBrowserEntityReference.entitiesReordered(document.querySelector("$container"));
}
if (typeof Drupal.entityBrowserMultiStepDisplay === 'object') {
  Drupal.entityBrowserMultiStepDisplay.entitiesReordered(document.querySelector("$container"));
}
END;
    $this->getSession()->executeScript($js);
  }

  /**
   * Test order change for Gallery.
   *
   * @throws \Exception
   */
  public function testOrderChange(): void {
    $node = $this->loadNodeByUuid('36b2e2b2-3df0-43eb-a282-d792b0999c07');
    $this->drupalGet($node->toUrl('edit-form'));

    $page = $this->getSession()->getPage();

    $this->editParagraph('field_paragraphs', 0);

    // Wait for all images to be displayed properly.
    $this->getSession()
      ->wait(10000, "jQuery('[data-drupal-selector=\"edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images-current\"] .media-form__item-widget--image').filter(function() {return jQuery(this).width() === 182;}).length === 5");

    $list_selector = 'div[data-drupal-selector="edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images-current"]';
    $this->scrollElementInView($list_selector . ' > *:nth-child(2)');

    $item_selector = "$list_selector .item-container";
    $this->sortableAfter("$item_selector:first-child", "$item_selector:nth-child(2)", $list_selector);

    $this->createScreenshot($this->getScreenshotFolder() . '/MediaGalleryModifyTest_AfterOrderChange_' . date('Ymd_His') . '.png');

    $secondElement = $page->find('xpath', '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images-current"]/div[2]');
    if (empty($secondElement)) {
      throw new \Exception('Second element in Gallery is not found');
    }

    $media = $this->loadMediaByUuid('159797c5-d9f9-4e27-b425-0f703a8a416d');
    $this->assertSame('media:' . $media->id(), $secondElement->getAttribute('data-entity-id'));

    $this->clickSave();

    $gallery = $this->loadMediaByUuid('df67621b-518f-4159-a59e-1bad0700800c');
    $this->clickCssSelector('#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 button.slick-next');

    // Check that, 2nd image is file: 26357237683_0891e46ba5_k.jpg.
    $fileNamePosition = $this->getSession()
      ->evaluateScript('jQuery(\'#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 div.slick-slide:not(.slick-cloned):nth(1) img\').attr(\'src\').indexOf("26357237683_0891e46ba5_k.jpg")');
    $this->assertNotEquals(-1, $fileNamePosition, 'For 2nd image in gallery, used file should be "26357237683_0891e46ba5_k.jpg".');
  }

  /**
   * Test add/remove Images in Gallery.
   *
   * Demo Article (node Id: 7) is used for testing.
   * Cases tested:
   *   - remove inside inline entity form
   *   - add inside entity browser
   *   - reorder inside entity browser
   *   - remove inside entity browser.
   */
  public function testAddRemove(): void {

    // Test remove inside inline entity form.
    $node = $this->loadNodeByUuid('36b2e2b2-3df0-43eb-a282-d792b0999c07');
    $this->drupalGet($node->toUrl('edit-form'));

    $this->editParagraph('field_paragraphs', 0);

    // Remove 2nd Image.
    $this->clickAjaxButtonCssSelector('[data-drupal-selector="edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images-current-items-1-remove-button"]');

    $this->clickSave();

    $gallery = $this->loadMediaByUuid('df67621b-518f-4159-a59e-1bad0700800c');
    $this->clickCssSelector('#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 button.slick-next');

    // Check that, there are 4 images in gallery.
    $numberOfImages = $this->getSession()
      ->evaluateScript('jQuery(\'#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 div.slick-slide:not(.slick-cloned)\').length;');
    $this->assertEquals(4, $numberOfImages, 'There should be 4 images in Gallery.');

    // Check that, 2nd image is file: 26315068204_24ffa6cfc4_o.jpg.
    $fileNamePosition = $this->getSession()
      ->evaluateScript('jQuery(\'#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 div.slick-slide:not(.slick-cloned):nth(1) img\').attr(\'src\').indexOf("26315068204_24ffa6cfc4_o.jpg")');
    $this->assertNotEquals(-1, $fileNamePosition, 'For 2nd image in gallery, used file should be "26315068204_24ffa6cfc4_o.jpg".');

    // Test add + reorder inside entity browser.
    $this->drupalGet($node->toUrl('edit-form'));

    $this->editParagraph('field_paragraphs', 0);

    // Click Select entities -> to open Entity Browser.
    $this->openEntityBrowser('edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images', 'multiple_image_browser');

    $this->uploadFile('/fixtures/reference.jpg');

    // Move new image -> that's 5th image in list, to 3rd position.
    $list_selector = '#edit-selected';
    $item_selector = "$list_selector .item-container";
    $this->sortableAfter("$item_selector:nth-child(5)", "$item_selector:nth-child(2)", $list_selector);

    $this->submitEntityBrowser('multiple_image_browser');

    $this->clickSave();

    // Check that, there are 5 images in gallery.
    $numberOfImages = $this->getSession()
      ->evaluateScript('jQuery(\'#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 div.slick-slide:not(.slick-cloned)\').length;');
    $this->assertEquals(5, $numberOfImages, 'There should be 5 images in Gallery.');

    $this->clickCssSelector('#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 button.slick-next');
    $this->clickCssSelector('#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 button.slick-next');

    // Check that, 3rd image is file: reference.jpg.
    $fileNamePosition = $this->getSession()
      ->evaluateScript('jQuery(\'#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 div.slick-slide:not(.slick-cloned):nth(2) img\').attr(\'src\').indexOf("reference.jpg")');
    $this->assertNotEquals(-1, $fileNamePosition, 'For 3rd image in gallery, used file should be "reference.jpg".');

    // Test remove inside entity browser.
    $this->drupalGet($node->toUrl('edit-form'));

    $this->editParagraph('field_paragraphs', 0);

    // Click Select entities -> to open Entity Browser.
    $this->openEntityBrowser('edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images', 'multiple_image_browser');

    $media = $this->getMediaByName('reference.jpg');
    $this->clickDrupalSelector('edit-selected-items-' . $media->id() . '-2-remove-button');

    $this->submitEntityBrowser('multiple_image_browser');

    $this->clickSave();

    // Check that, there are 4 images in gallery.
    $numberOfImages = $this->getSession()
      ->evaluateScript('jQuery(\'#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 div.slick-slide:not(.slick-cloned)\').length;');
    $this->assertEquals(4, $numberOfImages, 'There should be 4 images in Gallery.');

    $this->clickCssSelector('#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 button.slick-next');
    $this->clickCssSelector('#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 button.slick-next');

    // Check that, 3rd image is not file: reference.jpg.
    $fileNamePosition = $this->getSession()
      ->evaluateScript('jQuery(\'#slick-media-gallery-media-images-default-' . $gallery->id() . '-1 div.slick-slide:not(.slick-cloned):nth(2) img\').attr(\'src\').indexOf("reference.jpg")');
    $this->assertEquals(-1, $fileNamePosition, 'For 2nd image in gallery, used file should not be "reference.jpg".');
  }

}
