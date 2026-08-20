<?php

namespace Drupal\Tests\thunder_media\Functional;

use Drupal\Tests\thunder\Functional\ThunderTestBase;

/**
 * Tests the "AI disclosure upload only" setting.
 *
 * @group Thunder
 */
class AiDisclosureUploadOnlyTest extends ThunderTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['thunder_testing_demo', 'thunder_workflow'];

  /**
   * The field is editable by default, even on an existing media item.
   */
  public function testFieldIsEditableByDefault(): void {
    $this->logWithRole('editor');
    $media = $this->getMediaByName('Image 1');

    $this->drupalGet($media->toUrl('edit-form'));
    $this->assertSession()->fieldEnabled('field_digital_source_type');
  }

  /**
   * The field is disabled on an existing media item once configured.
   */
  public function testFieldIsDisabledAfterUploadWhenConfigured(): void {
    $this->config('thunder_media.settings')
      ->set('ai_disclosure_upload_only', TRUE)
      ->save();

    $this->logWithRole('editor');
    $media = $this->getMediaByName('Image 1');

    $this->drupalGet($media->toUrl('edit-form'));
    $this->assertSession()->fieldDisabled('field_digital_source_type');
  }

}
