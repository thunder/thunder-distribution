<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Tests the image upload.
 *
 * @group Thunder
 */
class ImageUploadTest extends ThunderJavascriptTestBase {

  use ThunderMediaLibraryTestTrait;

  /**
   * Test upload of webp images in media library.
   *
   * @group NoUpdate
   */
  public function testWebpImageUpload(): void {
    $this->drupalGet('node/add/article');

    $this->openMediaLibrary('field-teaser-media');
    $this->assertExpectedAjaxRequest(1);

    $this->uploadFile(__DIR__ . '/../../fixtures/reference.webp');
    $this->assertExpectedAjaxRequest(1);

    $this->clickCssSelector('.media-library-widget-modal .form-actions button.button--primary');
    $this->assertExpectedAjaxRequest(4);

    $this->clickCssSelector('.media-library-widget-modal .form-actions button.button--primary');
    $this->assertExpectedAjaxRequest(5);

    $this->assertSession()->elementTextContains('css', '.media-library-item__name', 'reference.webp');
  }

}
