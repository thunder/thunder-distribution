<?php

namespace Drupal\Tests\thunder\Functional;

/**
 * Tests the media system.
 *
 * @group Thunder
 */
class MediaTest extends ThunderTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_testing_demo',
    'thunder_workflow',
    'thunder_test_mock_request',
  ];

  /**
   * Test Image modifications (edit fields).
   */
  public function testImageEdit(): void {
    $this->logWithRole('editor');

    $media = $this->loadMediaByUuid('f5f7fc5d-b2b8-426a-adf3-ee6aff6379da');
    $this->drupalGet($media->toUrl('edit-form'));

    $this->submitForm([
      'name[0][value]' => "Media {$media->id()}",
      'field_image[0][alt]' => "Media {$media->id()} Alt Text",
      'field_image[0][title]' => "Media {$media->id()} Title",
      'field_expires[0][value][date]' => '2022-12-18',
      'field_expires[0][value][time]' => '01:02:03',
      'field_copyright[0][value]' => "Media {$media->id()} Copyright",
      'field_source[0][value]' => "Media {$media->id()} Source",
      'field_description[0][value]' => "<p>Media {$media->id()} Description</p>",
    ], 'Save');

    // Edit media and check are fields correct.
    $this->drupalGet($media->toUrl('edit-form'));

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

}
