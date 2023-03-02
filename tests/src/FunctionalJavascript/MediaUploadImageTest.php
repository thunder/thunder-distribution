<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

class MediaUploadImageTest extends ThunderJavascriptTestBase {

  use ThunderMediaLibraryTestTrait;

  /**
   * Test upload of Images in Media library.
   *
   * Media library is open from within an node edit form.
   */
  public function testAddRemove(): void {
    $this->drupalGet('node/add/article');
    $this->assertWaitOnAjaxRequest();

    $this->openMediaLibrary('field-teaser-media-selection');
    $this->uploadFile('/fixtures/reference.webp', TRUE);
    $this->submitMediaLibrary();

  }
}
