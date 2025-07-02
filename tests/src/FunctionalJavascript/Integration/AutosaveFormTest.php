<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

use Drupal\Tests\thunder\FunctionalJavascript\ThunderArticleTestTrait;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderFormFieldTestTrait;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderJavascriptTestBase;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderParagraphsTestTrait;

/**
 * Tests the autosave support for nodes in Thunder.
 *
 * @group Thunder
 */
class AutosaveFormTest extends ThunderJavascriptTestBase {

  use ThunderFormFieldTestTrait;
  use ThunderParagraphsTestTrait;
  use ThunderArticleTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Adjust the autosave form submission interval.
    $this->config('autosave_form.settings')
      ->set('interval', 2000)
      ->save();
  }

  /**
   * Tests the autosave functionality in an existing article.
   */
  public function testAutosaveInExistingEntity(): void {
    $node = $this->loadNodeByUuid('36b2e2b2-3df0-43eb-a282-d792b0999c07');
    $this->drupalGet($node->toUrl('edit-form'));
    $page = $this->getSession()->getPage();

    // Make some changes.
    $this->makeFormChanges();

    // Reload the page.
    $this->drupalGet($node->toUrl('edit-form'));

    // Reject the changes.
    $this->pressRejectButton();
    $term = $this->loadTermByUuid('35bdba6e-9b45-472a-8fda-11e7e69de71b');

    $this->assertEquals('[{"value":"' . $term->id() . '","label":"' . $term->getName() . '","entity_id":"' . $term->id() . '","info_label":"","editable":false}]', $page->findField('field_tags')->getValue());
    $this->assertEquals('Come to DrupalCon New Orleans', $page->findField('title[0][value]')->getValue());
    $this->assertSession()->elementNotExists('css', '.form-item--field-paragraphs-5-subform-field-text-0-value');

    // Make changes again.
    $this->makeFormChanges();

    // Reload the page.
    $this->drupalGet($node->toUrl('edit-form'));

    $this->pressRestoreButton();
    $this->assertEquals('[{"value":"' . $term->id() . '","label":"' . $term->getName() . '","entity_id":"' . $term->id() . '","info_label":"","editable":false},{"label":"Tag2","value":"Tag2"}]', $page->findField('field_tags')->getValue());
    $this->assertEquals('New title', $page->findField('title[0][value]')->getValue());
    $this->assertSession()->elementExists('css', '.form-item--field-paragraphs-7-subform-field-text-0-value');

    // Save the article.
    $this->clickSave();

    // Check some things.
    $this->assertSession()->pageTextContains('New title is scheduled to be published');
    $this->assertSession()->pageTextContains('Awesome quote');
  }

  /**
   * Press the restore button.
   */
  protected function pressRestoreButton(): void {
    $page = $this->getSession()->getPage();

    // Press restore button.
    $this->assertSession()->waitForText('A version of this page you were editing at');
    $restore_button = $page->find('css', '.autosave-form-resume-button');
    $restore_button->press();
  }

  /**
   * Press the reject button.
   */
  protected function pressRejectButton(): void {
    $page = $this->getSession()->getPage();

    // Press restore button.
    $this->assertSession()->waitForText('A version of this page you were editing at');
    $reject_button = $page->find('css', '.autosave-form-reject-button');
    $reject_button->press();
  }

  /**
   * Make some changes to the article.
   */
  protected function makeFormChanges(): void {
    $this->expandAllTabs();
    $this->addTextParagraph('field_paragraphs', 'Awesome quote', 'quote');

    $startTimestamp = strtotime('-2 days');
    $endTimestamp = strtotime('+1 day');

    $term = $this->loadTermByUuid('35bdba6e-9b45-472a-8fda-11e7e69de71b');
    $fieldValues = [
      'title[0][value]' => 'New title',
      'field_tags' => '[{"value":"' . $term->id() . '","label":"' . $term->getName() . '","entity_id":"' . $term->id() . '","info_label":"","editable":false},{"label":"Tag2","value":"Tag2"}]',
      'publish_on[0][value][date]' => date('Y-m-d', $startTimestamp),
      'publish_on[0][value][time]' => date('H:i:s', $startTimestamp),
      'unpublish_on[0][value][date]' => date('Y-m-d', $endTimestamp),
      'unpublish_on[0][value][time]' => date('H:i:s', $endTimestamp),
      'publish_state[0]' => 'published',
      'unpublish_state[0]' => 'unpublished',
    ];
    $this->setFieldValues($fieldValues);

    // Wait for autosave to be triggered.
    sleep(3);
  }

}
