<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\SortableTestTrait;

/**
 * Tests the Gallery media modification.
 *
 * @group Thunder
 */
class MediaGalleryModifyTest extends ThunderJavascriptTestBase {

  use ThunderMediaLibraryTestTrait;
  use ThunderParagraphsTestTrait;
  use SortableTestTrait;

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  protected function sortableUpdate($item, $from, $to = NULL): void {
    // See core/modules/media_library/js/media_library.widget.es6.js.
    $script = <<<JS
(function ($) {
    var selection = document.querySelectorAll('.js-media-library-selection');
    selection.forEach(function (widget) {
        $(widget).children().each(function (index, child) {
            $(child).find('.js-media-library-item-weight').val(index);
        });
    });
})(jQuery)

JS;

    $this->getSession()->executeScript($script);
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

    $list_selector = '#field_media_images-media-library-wrapper-field_paragraphs-0-subform-field_media-0-inline_entity_form .js-media-library-selection';
    $this->scrollElementInView($list_selector . ' > *:nth-child(2)');

    $this->sortableAfter($list_selector . ' [data-media-library-item-delta="0"]', $list_selector . ' [data-media-library-item-delta="1"]', $list_selector);

    $this->createScreenshot($this->getScreenshotFolder() . '/MediaGalleryModifyTest_AfterOrderChange_' . date('Ymd_His') . '.png');

    $secondElement = $page->find('xpath', '//div[@data-drupal-selector="edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images-selection"]/div[2]/input[@type="hidden"]');
    if (empty($secondElement)) {
      throw new \Exception('Second element in Gallery is not found');
    }

    $media = $this->loadMediaByUuid('159797c5-d9f9-4e27-b425-0f703a8a416d');
    $this->assertSame($media->id(), $secondElement->getAttribute('value'));

    $this->clickSave();

    $this->clickCssSelector('div.slick--initialized.slick--field-media-images button.slick-next');

    // Check that, 2nd image is file: 26357237683_0891e46ba5_k.jpg.
    $fileNamePosition = $this->getSession()
      ->evaluateScript('jQuery(\'div.slick--initialized.slick--field-media-images div.slick-slide:not(.slick-cloned):nth(1) img\').attr(\'src\').indexOf("26357237683_0891e46ba5_k.jpg")');
    $this->assertNotEquals(-1, $fileNamePosition, 'For 2nd image in gallery, used file should be "26357237683_0891e46ba5_k.jpg".');
  }

  /**
   * Test add/remove Images in Gallery.
   *
   * Demo Article (node Id: 7) is used for testing.
   * Cases tested:
   *   - remove inside inline entity form
   *   - add inside media library
   *   - reorder inside media library
   *   - remove inside media library.
   */
  public function testAddRemove(): void {

    // Test remove inside inline entity form.
    $node = $this->loadNodeByUuid('36b2e2b2-3df0-43eb-a282-d792b0999c07');
    $this->drupalGet($node->toUrl('edit-form'));

    $this->editParagraph('field_paragraphs', 0);

    // Remove 2nd Image.
    $this->clickDrupalSelector('edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images-selection-1-remove-button');

    $this->clickSave();

    $this->clickCssSelector('div.slick--initialized.slick--field-media-images button.slick-next');

    // Check that, there are 4 images in gallery.
    $numberOfImages = $this->getSession()
      ->evaluateScript('jQuery(\'div.slick--initialized.slick--field-media-images div.slick-slide:not(.slick-cloned)\').length;');
    $this->assertEquals(4, $numberOfImages, 'There should be 4 images in Gallery.');

    // Check that, 2nd image is file: 26315068204_24ffa6cfc4_o.jpg.
    $fileNamePosition = $this->getSession()
      ->evaluateScript('jQuery(\'div.slick--initialized.slick--field-media-images div.slick-slide:not(.slick-cloned):nth(1) img\').attr(\'src\').indexOf("26315068204_24ffa6cfc4_o.jpg")');
    $this->assertNotEquals(-1, $fileNamePosition, 'For 2nd image in gallery, used file should be "26315068204_24ffa6cfc4_o.jpg".');

    // Test add + reorder inside media library.
    $this->drupalGet($node->toUrl('edit-form'));

    $this->editParagraph('field_paragraphs', 0);

    // Click Select entities -> to open media library.
    $this->openMediaLibrary('field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images');

    $this->uploadFile(__DIR__ . '/../../fixtures/reference.jpg', TRUE);

    $this->submitMediaLibrary();

    // Move new image -> that's 5th image in list, to 3rd position.
    $this->sortableAfter('[data-media-library-item-delta="4"]', '[data-media-library-item-delta="1"]', '#field_media_images-media-library-wrapper-field_paragraphs-0-subform-field_media-0-inline_entity_form .js-media-library-selection');

    $this->clickSave();

    // Check that, there are 5 images in gallery.
    $numberOfImages = $this->getSession()
      ->evaluateScript('jQuery(\'div.slick--initialized.slick--field-media-images div.slick-slide:not(.slick-cloned)\').length;');
    $this->assertEquals(5, $numberOfImages, 'There should be 5 images in Gallery.');

    $this->clickCssSelector('div.slick--initialized.slick--field-media-images button.slick-next');
    $this->clickCssSelector('div.slick--initialized.slick--field-media-images button.slick-next');

    // Check that, 3rd image is file: reference.jpg.
    $fileNamePosition = $this->getSession()
      ->evaluateScript('jQuery(\'div.slick--initialized.slick--field-media-images div.slick-slide:not(.slick-cloned):nth(2) img\').attr(\'src\').indexOf("reference.jpg")');
    $this->assertNotEquals(-1, $fileNamePosition, 'For 3rd image in gallery, used file should be "reference.jpg".');

    // Test remove inside media library.
    $this->drupalGet($node->toUrl('edit-form'));

    $this->editParagraph('field_paragraphs', 0);

    // Click Select entities -> to open media library.
    $this->openMediaLibrary('field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images');

    $media = $this->getMediaByName('reference.jpg');
    $this->toggleMedia([$media->id()]);

    $this->submitMediaLibrary();

    $this->clickSave();

    // Check that, there are 4 images in gallery.
    $numberOfImages = $this->getSession()
      ->evaluateScript('jQuery(\'div.slick--initialized.slick--field-media-images div.slick-slide:not(.slick-cloned)\').length;');
    $this->assertEquals(4, $numberOfImages, 'There should be 4 images in Gallery.');

    $this->clickCssSelector('div.slick--initialized.slick--field-media-images button.slick-next');
    $this->clickCssSelector('div.slick--initialized.slick--field-media-images button.slick-next');

    // Check that, 3rd image is not file: reference.jpg.
    $fileNamePosition = $this->getSession()
      ->evaluateScript('jQuery(\'div.slick--initialized.slick--field-media-images div.slick-slide:not(.slick-cloned):nth(2) img\').attr(\'src\').indexOf("reference.jpg")');
    $this->assertEquals(-1, $fileNamePosition, 'For 2nd image in gallery, used file should not be "reference.jpg".');
  }

}
