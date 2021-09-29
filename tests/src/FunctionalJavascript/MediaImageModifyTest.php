<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Tests the Image media modification.
 *
 * @group Thunder
 */
class MediaImageModifyTest extends ThunderJavascriptTestBase {

  use ThunderEntityBrowserTestTrait;
  use ThunderParagraphsTestTrait;

  /**
   * Test Focal Point change.
   */
  public function testFocalPointChange() {

    $media = $this->loadMediaByUuid('f5f7fc5d-b2b8-426a-adf3-ee6aff6379da');
    $this->drupalGet($media->toUrl('edit-form'));

    $this->createScreenshot($this->getScreenshotFolder() . '/MediaImageModifyTest_BeforeFocalPointChange_' . date('Ymd_His') . '.png');

    $this->getSession()
      ->getDriver()
      ->executeScript('var e = new jQuery.Event("click"); e.offsetX = 48; e.offsetY = 15; jQuery(".focal-point-wrapper img").trigger(e);');

    $this->createScreenshot($this->getScreenshotFolder() . '/MediaImageModifyTest_AfterFocalPointChange_' . date('Ymd_His') . '.png');

    $this->clickSave();

    $media = $this->loadMediaByUuid('f5f7fc5d-b2b8-426a-adf3-ee6aff6379da');
    $img = $media->get('field_image')->target_id;

    $file = File::load($img);
    $path = $file->getFileUri();

    $derivativeUri = ImageStyle::load('teaser')->buildUri($path);

    ImageStyle::load('teaser')->createDerivative($path, $derivativeUri);

    $image1 = new \Imagick($derivativeUri);
    $image2 = new \Imagick(realpath(dirname(__FILE__) . '/../../fixtures/reference.jpg'));

    $result = $image1->compareImages($image2, \Imagick::METRIC_MEANSQUAREERROR);

    $this->assertTrue($result[1] < 0.01, 'Images are identical');

    $image1->clear();
    $image2->clear();
  }

  /**
   * Test Image modifications (edit fields).
   */
  public function testImageEdit() {
    $page = $this->getSession()->getPage();

    $media = $this->loadMediaByUuid('f5f7fc5d-b2b8-426a-adf3-ee6aff6379da');
    $this->drupalGet($media->toUrl('edit-form'));

    $this->assertWaitOnAjaxRequest();

    $page->fillField('name[0][value]', "Media {$media->id()}");
    $page->fillField('field_image[0][alt]', "Media {$media->id()} Alt Text");
    $page->fillField('field_image[0][title]', "Media {$media->id()} Title");
    $this->setRawFieldValue('field_expires[0][value][date]', '2022-12-18');
    $this->setRawFieldValue('field_expires[0][value][time]', '01:02:03');
    $page->fillField('field_copyright[0][value]', "Media {$media->id()} Copyright");
    $page->fillField('field_source[0][value]', "Media {$media->id()} Source");

    $this->fillCkEditor('#edit-field-description-0-value', "Media {$media->id()} Description");

    $this->createScreenshot($this->getScreenshotFolder() . '/MediaImageModifyTest_BeforeImageEditSave_' . date('Ymd_His') . '.png');

    $this->clickSave();

    // Edit media and check are fields correct.
    $this->drupalGet($media->toUrl('edit-form'));

    $this->createScreenshot($this->getScreenshotFolder() . '/MediaImageModifyTest_AfterImageEdit_' . date('Ymd_His') . '.png');

    $this->assertSession()
      ->fieldValueEquals('name[0][value]', "Media {$media->id()}");
    $this->assertSession()
      ->fieldValueEquals('field_image[0][alt]', "Media {$media->id()} Alt Text");
    $this->assertSession()
      ->fieldValueEquals('field_image[0][title]', "Media {$media->id()} Title");
    $this->assertSession()
      ->fieldValueEquals('field_expires[0][value][date]', '2022-12-18');
    $this->assertSession()
      ->fieldValueEquals('field_expires[0][value][time]', '01:02:03');
    $this->assertSession()
      ->fieldValueEquals('field_copyright[0][value]', "Media {$media->id()} Copyright");
    $this->assertSession()
      ->fieldValueEquals('field_source[0][value]', "Media {$media->id()} Source");
    $this->assertSession()
      ->fieldValueEquals('field_description[0][value]', "<p>Media {$media->id()} Description</p>");
  }

  /**
   * Test add/remove image in image paragraph.
   *
   * Demo Article (node Id: 6) is used for testing.
   * Cases tested:
   *   - remove inside inline entity form
   *   - add inside entity browser
   *   - reorder inside entity browser
   *   - remove inside entity browser.
   */
  public function testRemoveAdd() {

    // Test remove inside inline entity form.
    $node = $this->loadNodeByUuid('0bd5c257-2231-450f-b4c2-ab156af7b78d');
    $this->drupalGet($node->toUrl('edit-form'));

    $page = $this->getSession()->getPage();

    $this->editParagraph($page, 'field_paragraphs', 0);

    // Remove image.
    $this->clickAjaxButtonCssSelector('[data-drupal-selector="edit-field-paragraphs-0-subform-field-image-current-items-0-remove-button"]');
    $this->assertWaitOnAjaxRequest();

    // Check that there are no errors.
    $this->assertSession()
      ->elementNotExists('css', '[data-drupal-selector="edit-field-paragraphs-0-subform-field-image-wrapper"] div.messages--error');

    // Click Select entities -> to open Entity Browser.
    $this->openEntityBrowser($page, 'edit-field-paragraphs-0-subform-field-image-entity-browser-entity-browser-open-modal', 'image_browser');

    // Select another image and store filename.
    $this->clickButtonCssSelector($page, '#entity-browser-image-browser-form div.view-content > div.views-row:nth-child(1)');
    $fileName = $this->getSession()->evaluateScript('jQuery(\'#entity-browser-image-browser-form div.view-content > div.views-row:nth-child(1) img\').attr(\'src\').split(\'?\')[0].split(\'/\').splice(-1);');
    $this->clickButtonDrupalSelector($page, 'edit-submit');
    $this->getSession()->switchToIFrame();
    $this->assertWaitOnAjaxRequest();

    // Save paragraph.
    $this->clickAjaxButtonCssSelector('[name="field_paragraphs_0_collapse"]');

    $this->assertEquals($fileName, $this->getSession()->evaluateScript('jQuery(\'[data-drupal-selector="edit-field-paragraphs-0-preview"] div.paragraph-preview__thumbnail img\').attr(\'src\').split(\'?\')[0].split(\'/\').splice(-1)'), 'Image file should be identical to previously selected.');
  }

}
