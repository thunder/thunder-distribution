<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

use Drupal\Tests\thunder\FunctionalJavascript\ThunderJavascriptTestBase;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderParagraphsTestTrait;
use Drupal\media\Entity\Media;

/**
 * Tests integration with the entity_reference_actions and views_bulk_edit.
 *
 * @group Thunder
 */
class EntityReferenceActionsTest extends ThunderJavascriptTestBase {

  use ThunderParagraphsTestTrait;

  /**
   * Test editing of media items in an embedded gallery.
   */
  public function testMediaEditInArticle(): void {

    $node = $this->loadNodeByUuid('36b2e2b2-3df0-43eb-a282-d792b0999c07');
    $this->drupalGet($node->toUrl('edit-form'));

    $this->editParagraph('field_paragraphs', 0);

    // Wait for all images to be displayed properly.
    $this->getSession()
      ->wait(10000, "jQuery('[data-drupal-selector=\"edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images-current\"] .media-form__item-widget--image').filter(function() {return jQuery(this).width() === 182;}).length === 5");

    $this->scrollElementInView('#field_media_images_media_edit_action_button');
    $this->getSession()->getPage()->pressButton('Edit all media items');
    $this->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->checkField('media[image][_field_selector][field_copyright]');
    $this->getSession()->getPage()->fillField('media[image][field_copyright][0][value]', 'Test copyright');
    $this->getSession()->getPage()->selectFieldOption('media[image][field_copyright_change_method]', 'replace');

    $this->assertSession()->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Confirm');

    $this->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('Action was successfully applied');

    foreach (array_column($node->field_paragraphs->entity->field_media->entity->field_media_images->getValue(), 'target_id') as $media_id) {
      $media = Media::load($media_id);
      $this->assertSame('Test copyright', $media->field_copyright->value);
    }
  }

}
