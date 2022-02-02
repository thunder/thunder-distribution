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
   *   - add inside entity browser.
   */
  public function testRemoveAdd(): void {

    // Test remove inside inline entity form.
    $node = $this->loadNodeByUuid('0bd5c257-2231-450f-b4c2-ab156af7b78d');
    $this->drupalGet($node->toUrl('edit-form'));

    $this->editParagraph('field_paragraphs', 0);

    // Remove image.
    $this->clickDrupalSelector('edit-field-paragraphs-0-subform-field-image-selection-0-remove-button');

    // Check that there are no errors.
    $this->assertSession()
      ->elementNotExists('css', '[data-drupal-selector="edit-field-paragraphs-0-subform-field-image-wrapper"] div.messages--error');

    $image2 = $this->loadMediaByUuid('a4b2fa51-8340-4982-b792-92e060b71eb9');
    $this->selectMedia('field-paragraphs-0-subform-field-image', [$image2->id()]);

    // Save paragraph.
    $this->clickAjaxButtonCssSelector('[name="field_paragraphs_0_collapse"]');
    /** @var \Drupal\file\FileInterface $file */
    $file = $image2->field_image->entity;
    $this->assertEquals([$file->getFilename()], $this->getSession()->evaluateScript('jQuery(\'[data-drupal-selector="edit-field-paragraphs-0-preview"] article.media--view-mode-paragraph-preview img\').attr(\'src\').split(\'?\')[0].split(\'/\').splice(-1)'), 'Image file should be identical to previously selected.');
  }

}
