<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

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

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Adjust the autosave form submission interval.
    $this->config('autosave_form.settings')
      ->set('interval', 2000)
      ->save();
  }

  /**
   * Tests the autosave functionality in an existing article.
   */
  public function testAutosaveInExistingEntity() {
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
    $this->assertEquals([$term->id()], $page->findField('field_tags[]')->getValue());
    $this->assertEquals('Come to DrupalCon New Orleans', $page->findField('title[0][value]')->getValue());
    $this->assertEmpty($page->find('css', '.form-item-field-paragraphs-3-subform-field-text-0-value'));

    // Make changes again.
    $this->makeFormChanges();

    // Reload the page.
    $this->drupalGet($node->toUrl('edit-form'));

    $this->pressRestoreButton();
    $this->assertEquals([$term->id(), '$ID:Tag2'], $page->findField('field_tags[]')->getValue());
    $this->assertEquals('New title', $page->findField('title[0][value]')->getValue());
    $this->assertNotEmpty($page->find('css', '.form-item-field-paragraphs-3-subform-field-text-0-value'));

    // Save the article.
    $this->clickSave();

    // Check some things.
    $this->assertSession()->pageTextContains('New title is scheduled to be published');
    $this->assertSession()->pageTextContains('Awesome quote');
  }

  /**
   * Press the restore button.
   */
  protected function pressRestoreButton() {
    $page = $this->getSession()->getPage();

    // Press restore button.
    $this->assertSession()->waitForText('A version of this page you were editing at');
    $restore_button = $page->find('css', '.autosave-form-resume-button');
    $restore_button->press();
  }

  /**
   * Press the reject button.
   */
  protected function pressRejectButton() {
    $page = $this->getSession()->getPage();

    // Press restore button.
    $this->assertSession()->waitForText('A version of this page you were editing at');
    $reject_button = $page->find('css', '.autosave-form-reject-button');
    $reject_button->press();
  }

  /**
   * Make some changes to the article.
   */
  protected function makeFormChanges() {
    $page = $this->getSession()->getPage();

    $this->expandAllTabs();
    $this->addTextParagraph('field_paragraphs', 'Awesome quote', 'quote');

    $startTimestamp = strtotime('-2 days');
    $endTimestamp = strtotime('+1 day');

    $term = $this->loadTermByUuid('35bdba6e-9b45-472a-8fda-11e7e69de71b');
    $fieldValues = [
      'title[0][value]' => 'New title',
      'field_tags[]' => [[$term->id(), $term->getName()], 'Tag2'],
      'publish_on[0][value][date]' => date('Y-m-d', $startTimestamp),
      'publish_on[0][value][time]' => date('H:i:s', $startTimestamp),
      'unpublish_on[0][value][date]' => date('Y-m-d', $endTimestamp),
      'unpublish_on[0][value][time]' => date('H:i:s', $endTimestamp),
      'publish_state[0]' => 'published',
      'unpublish_state[0]' => 'unpublished',
    ];
    $this->setFieldValues($page, $fieldValues);

    // Wait for autosave to be triggered.
    sleep(3);
  }

}
