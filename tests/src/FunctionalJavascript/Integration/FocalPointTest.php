<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Tests the Image media modification.
 *
 * @group Thunder
 */
class FocalPointTest extends ThunderJavascriptTestBase {

  /**
   * Test that focal_point preview is rendered in overlay.
   */
  public function testFocalPointPreview(): void {
    $node = $this->loadNodeByUuid('0bd5c257-2231-450f-b4c2-ab156af7b78d');
    $this->drupalGet($node->toUrl('edit-form'));
    $this->clickDrupalSelector('edit-field-teaser-media-selection-0-edit');
    $this->clickDrupalSelector('edit-field-image-0-preview-preview-link');

    $this->assertSession()->elementExists('css', '#focal-point-derivatives .focal-point-derivative-preview-image');
    $this->assertSession()->elementExists('css', '.focal-point-original-image > #focal-point-preview-image');
  }

}
