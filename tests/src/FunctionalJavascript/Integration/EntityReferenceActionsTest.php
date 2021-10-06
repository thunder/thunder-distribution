<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

use Drupal\Tests\thunder\FunctionalJavascript\ThunderJavascriptTestBase;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderParagraphsTestTrait;

/**
 * Tests integration with the entity_reference_actions and views_bulk_edit.
 *
 * @group Thunder
 */
class EntityReferenceActionsTest extends ThunderJavascriptTestBase {

  use ThunderParagraphsTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['thunder_testing_demo'];

  /**
   * Test editing of media items in an embedded gallery.
   */
  public function testMediaEditInArticle() {

    $node = $this->loadNodeByUuid('36b2e2b2-3df0-43eb-a282-d792b0999c07');
    $this->drupalGet($node->toUrl('edit-form'));
    $page = $this->getSession()->getPage();

    $this->editParagraph($page, 'field_paragraphs', 0);

    // Wait for all images to be displayed properly.
    $this->getSession()
      ->wait(10000, "jQuery('[data-drupal-selector=\"edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images-current\"] .media-form__item-widget--image').filter(function() {return jQuery(this).width() === 182;}).length === 5");

    $this->scrollElementInView('#field_media_images_media_edit_action_button');
    $this->getSession()->getPage()->pressButton('Edit all media items');
    $this->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->checkField('media[image][_field_selector][field_copyright]');
    $this->getSession()->getPage()->fillField('media[image][field_copyright][0][value]', 'Test copyright');

    $this->assertSession()->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Confirm');

    $this->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('Action was successfully applied');

    for ($i = 0; $i < 4; $i++) {
      $this->clickAjaxButtonCssSelector('[data-drupal-selector="edit-field-paragraphs-0-subform-field-media-0-inline-entity-form-field-media-images-current-items-' . $i . '-edit-button"]');
      $this->assertWaitOnAjaxRequest();
      $this->assertSession()->fieldValueEquals('field_copyright[0][value]', 'Test copyright');
      $this->assertSession()->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save');
    }
  }

}
