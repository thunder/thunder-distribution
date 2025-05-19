<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

use Drupal\Tests\thunder\FunctionalJavascript\ThunderFormFieldTestTrait;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderJavascriptTestBase;

/**
 * Test for update hook changes.
 *
 * @group Thunder
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript\Integration
 */
class InlineEntityFormTest extends ThunderJavascriptTestBase {

  use ThunderFormFieldTestTrait;

  /**
   * Test saving collapsed gallery paragraph.
   *
   * Test saving changes in inline entity form using the
   * inline_entity_form_simple widget inside gallery paragraph when the
   * paragraph form is collapsed.
   *
   * Demo Article (node Id: 7) is used for testing.
   */
  public function testGalleryCollapse(): void {

    // Test saving inline entity form when collapsing paragraph form.
    $node = $this->loadNodeByUuid('36b2e2b2-3df0-43eb-a282-d792b0999c07');
    $this->drupalGet($node->toUrl('edit-form'));
    $this->getSession()->getPage()->find('css', '[data-drupal-selector="edit-gin-sticky-actions"] .meta-sidebar__trigger')->click();

    // Edit gallery paragraph.
    $this->clickDrupalSelector('field-paragraphs-0-edit-2');
    $this->setFieldValue('field_paragraphs[0][subform][field_media][0][inline_entity_form][name][0][value]', 'New gallery name before collapse');

    // Collapse parargraph form.
    $this->clickCssSelector('[name="field_paragraphs_0_collapse"]');
    $this->clickSave();

    // Re-open edit form, value has changed.
    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()
      ->pageTextContains('New gallery name before collapse');
  }

}
