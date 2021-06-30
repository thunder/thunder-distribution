<?php

namespace Drupal\Tests\thunder_media\Functional;

use Drupal\Tests\thunder\Functional\ThunderTestBase;

/**
 * Tests for transliteration of file names.
 *
 * @group Thunder
 */
class FileRemoveButtonTest extends ThunderTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['thunder_testing_demo', 'thunder_workflow'];

  /**
   * Test for transliteration of file name.
   */
  public function testRemoveButtonGone() {

    $this->logWithRole('editor');
    $media = $this->getMediaByName('Image 1');

    $this->drupalGet($media->toUrl('edit-form'));
    $this->assertSession()->elementNotExists('css', '#edit-field-image-0-remove-button');

    $this->config('thunder_media.settings')
      ->set('enable_filefield_remove_button', TRUE)
      ->save();

    $this->drupalGet($media->toUrl('edit-form'));
    $this->assertSession()->elementExists('css', '#edit-field-image-0-remove-button');

  }

}
