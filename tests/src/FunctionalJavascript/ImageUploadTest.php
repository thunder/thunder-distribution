<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Tests the Gallery media modification.
 *
 * @group Thunder
 */
class ImageUploadTest extends ThunderJavascriptTestBase {

  use ThunderMediaLibraryTestTrait;
  use ThunderParagraphsTestTrait;

  /**
   * Test upload of Images in Media library.
   *
   * Media library is open from within and node edit form.
   */
  public function testImageUpload(): void {
    $this->drupalGet('node/add/article');
    $this->assertWaitOnAjaxRequest();

    $this->openMediaLibrary('field-teaser-media');
    $this->uploadFile('/fixtures/reference.webp', TRUE);
    $this->submitMediaLibrary();
    $this->assertSession()->elementTextContains('css', '.media-library-item__name', 'reference.webp');
  }

}
