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
   * The field is disabled on an existing media item by default.
   */
  public function testFieldIsDisabledByDefault(): void {
    $this->logWithRole('editor');
    $media = $this->getMediaByName('Image 1');

    $this->drupalGet($media->toUrl('edit-form'));
    $this->assertSession()->fieldDisabled('field_digital_source_type');
  }

  /**
   * The field is editable once the lock is explicitly disabled.
   */
  public function testFieldIsEditableWhenLockDisabled(): void {
    $this->config('thunder_media.settings')
      ->set('ai_disclosure_upload_only', FALSE)
      ->save();

    $this->logWithRole('editor');
    $media = $this->getMediaByName('Image 1');

    $this->drupalGet($media->toUrl('edit-form'));
    $this->assertSession()->fieldEnabled('field_digital_source_type');
  }

  /**
   * The lock is enforced in the presave hook, not just the disabled widget.
   *
   * A direct entity API save never goes through the widget's form-alter.
   */
  public function testFieldCannotBeChangedViaApiWhenConfigured(): void {
    $this->config('thunder_media.settings')
      ->set('ai_disclosure_upload_only', TRUE)
      ->save();

    $media = $this->getMediaByName('Image 1');
    $original = $media->get('field_digital_source_type')->value;

    $media->set('field_digital_source_type', 'trainedAlgorithmicMedia');
    $media->save();

    $media = $this->getMediaByName('Image 1', TRUE);
    $this->assertSame($original, $media->get('field_digital_source_type')->value);
  }

}
