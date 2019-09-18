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
    \Drupal::configFactory()
      ->getEditable('autosave_form.settings')
      ->set('interval', 2000)
      ->save();
  }

  /**
   * Tests the autosave functionality in an existing article.
   */
  public function testAutosaveInExistingEntity() {
    $this->drupalGet('node/7/edit');
    $page = $this->getSession()->getPage();

    // Make some changes.
    $this->setFieldValue($page, 'field_tags[]', [[5, 'Drupal'], 'Tag2']);
    $this->addTextParagraph('field_paragraphs', 'Awesome quote', 'quote');

    $this->expandAllTabs();
    $startTimestamp = strtotime('-2 days');
    $endTimestamp = strtotime('+1 day');
    $fieldValues = [
      'publish_on[0][value][date]' => date('Y-m-d', $startTimestamp),
      'publish_on[0][value][time]' => date('H:i:s', $startTimestamp),
      'unpublish_on[0][value][date]' => date('Y-m-d', $endTimestamp),
      'unpublish_on[0][value][time]' => date('H:i:s', $endTimestamp),
      'publish_state[0]' => 'published',
      'unpublish_state[0]' => 'unpublished',
    ];
    $this->setFieldValues($page, $fieldValues);

    $this->assertEquals([5, '$ID:Tag2'], $page->findField('field_tags[]')->getValue());

    // Wait for autosave to be triggered.
    sleep(3);

    // Reload the page.
    $this->drupalGet('node/7/edit');

    // Press restore button.
    $this->assertSession()->waitForText('A version of this page you were editing at');
    $restore_button = $page->find('css', '.autosave-form-resume-button');
    $this->assertNotEmpty($restore_button);
    $restore_button->press();

    // Check saved states.
    $this->assertEquals([5, '$ID:Tag2'], $page->findField('field_tags[]')->getValue());

    // Save the article.
    $this->clickSave();

    // Check some things.
    $this->assertSession()->pageTextContains('This post is unpublished and will be published');
    $this->assertSession()->pageTextContains('Awesome quote');
  }

}
