<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderArticleTestTrait;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderJavascriptTestBase;

/**
 * Tests the autofill support for nodes in Thunder.
 *
 * @group Thunder
 */
class AutofillTest extends ThunderJavascriptTestBase {

  use ThunderArticleTestTrait;
  use NodeCreationTrait;

  /**
   * Tests the autofill of a new field based on the node title.
   */
  public function testAutofill() {

    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/article');

    $page->fillField('field_channel', 1);
    $page->fillField('title[0][value]', 'Autofill test title');

    // The autofill field should have the same value as the title.
    $this->assertSession()
      ->fieldValueEquals('field_seo_title[0][value]', 'Autofill test title');
    $page->findButton('Save')->click();

    // After reload meta title should be same as seo_title.
    $this->assertSession()->elementContains('xpath', '//head/title', 'Autofill test title');

    $node = $this->getNodeByTitle('Autofill test title');
    $edit_url = $node->toUrl('edit-form');

    // Open the created node again. When changing the title, the autofill
    // field should change since values are identical.
    $this->drupalGet($edit_url);
    $page->fillField('title[0][value]', 'My adjusted autofill test title');
    $this->assertSession()
      ->fieldValueEquals('field_seo_title[0][value]', 'My adjusted autofill test title');

    // If the autofilled field was manipulated once it should not be autofilled
    // anymore. Manipulation is done by pressing backspace in the text field.
    $this->drupalGet('node/add/article');
    $autofill_field = $page->findField('field_seo_title[0][value]');
    $autofill_field->keyPress(8);
    $page->fillField('title[0][value]', 'My adjusted autofill test title');
    $this->assertSession()->fieldValueEquals('field_seo_title[0][value]', '');
  }

}
