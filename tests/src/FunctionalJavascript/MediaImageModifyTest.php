<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\user\Entity\Role;

/**
 * Tests the Image media modification.
 *
 * @group Thunder
 */
class MediaImageModifyTest extends ThunderJavascriptTestBase {

  use ThunderEntityBrowserTestTrait;
  use ThunderParagraphsTestTrait;
  use ThunderFormFieldTestTrait;
  use ThunderCkEditorTestTrait;

  /**
   * Test Focal Point change.
   */
  public function testFocalPointChange(): void {

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
    $image2 = new \Imagick(realpath(__DIR__ . '/../../fixtures/reference.jpg'));

    $result = $image1->compareImages($image2, \Imagick::METRIC_MEANSQUAREERROR);

    $this->assertTrue($result[1] < 0.01, 'Images are identical');

    $image1->clear();
    $image2->clear();
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
  public function testRemoveAdd(): void {

    // Test remove inside inline entity form.
    $node = $this->loadNodeByUuid('0bd5c257-2231-450f-b4c2-ab156af7b78d');
    $this->drupalGet($node->toUrl('edit-form'));

    $this->editParagraph('field_paragraphs', 0);

    // Remove image.
    $this->clickAjaxButtonCssSelector('[data-drupal-selector="edit-field-paragraphs-0-subform-field-image-current-items-0-remove-button"]');

    // Check that there are no errors.
    $this->assertSession()
      ->elementNotExists('css', '[data-drupal-selector="edit-field-paragraphs-0-subform-field-image-wrapper"] div.messages--error');

    // Click Select entities -> to open Entity Browser.
    $this->openEntityBrowser('edit-field-paragraphs-0-subform-field-image', 'image_browser');

    // Select another image and store filename.
    $this->clickCssSelector('#entity-browser-image-browser-form div.view-content > div.views-row:nth-child(1)');
    $fileName = $this->getSession()->evaluateScript('jQuery(\'#entity-browser-image-browser-form div.view-content > div.views-row:nth-child(1) img\').attr(\'src\').split(\'?\')[0].split(\'/\').splice(-1);');
    $this->clickDrupalSelector('edit-submit');
    $this->getSession()->switchToIFrame();
    $this->assertWaitOnAjaxRequest();

    // Save paragraph.
    $this->clickAjaxButtonCssSelector('[name="field_paragraphs_0_collapse"]');

    $this->assertEquals($fileName, $this->getSession()->evaluateScript('jQuery(\'[data-drupal-selector="edit-field-paragraphs-0-preview"] div.paragraph-preview__thumbnail img\').attr(\'src\').split(\'?\')[0].split(\'/\').splice(-1)'), 'Image file should be identical to previously selected.');

    // Go to the media view and try deleting the image media.
    $this->drupalGet('admin/content/media');
    $this->getSession()->getPage()->find('css', 'div.view-media')->clickLink('Thunder City');
    $media = $this->loadMediaByUuid('5d719c64-7f32-4062-9967-9874f5ca3eba');
    $this->assertSession()->addressMatches('#media/' . $media->id() . '/edit$#');
    /** @var \Drupal\file\FileInterface $file */
    $file = $media->get($media->getSource()->getConfiguration()['source_field'])->entity;
    $this->assertFileExists($file->getFileUri());
    $this->getSession()->getPage()->find('css', 'div.block-local-tasks-block')->clickLink('Delete');
    $this->assertSession()->fieldNotExists('also_delete_file');
    $this->assertSession()->pageTextContains('This action cannot be undone. The file attached to this media is owned by admin so will be retained.');
    Role::load(static::$defaultUserRole)->grantPermission('delete any file')->save();
    $this->getSession()->reload();
    $this->assertSession()->fieldExists('also_delete_file')->check();
    $this->getSession()->getPage()->pressButton('Delete');
    $this->assertFileDoesNotExist($file->getFileUri());
  }

}
