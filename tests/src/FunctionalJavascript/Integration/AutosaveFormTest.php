<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

use Drupal\node\Entity\Node;
use Drupal\Tests\autosave_form\FunctionalJavascript\AutosaveFromTestTrait;
use Drupal\Tests\autosave_form\FunctionalJavascript\ContentEntity\ContentEntityAutosaveFormTestTrait;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderFormFieldTestTrait;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderJavascriptTestBase;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderParagraphsTestTrait;

/**
 * Tests the autosave support for nodes in Thunder.
 *
 * @group Thunder
 */
class AutosaveFormTest extends ThunderJavascriptTestBase {

  use AutosaveFromTestTrait;
  use ContentEntityAutosaveFormTestTrait;
  use ThunderFormFieldTestTrait;
  use ThunderParagraphsTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['autosave_form_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Adjust the autosave form submission interval.
    \Drupal::configFactory()
      ->getEditable('autosave_form.settings')
      ->set('interval', $this->interval)
      ->save();
  }

  /**
   * Tests the autosave functionality in an existing article.
   */
  public function testAutosaveInExistingEntity() {

    $entity = Node::load(7);
    $entity_id = $entity->id();
    $entity_form_edit_url = $entity->toUrl('edit-form');

    $this->drupalGet($entity_form_edit_url);
    $this->assertAutosaveFormLibraryLoaded(TRUE);

    // Wait for at least having two autosave submits being executed and assert
    // that with no changes there will be no autosave states created.
    $this->assertTrue($this->waitForAutosaveSubmits(2));
    $this->assertEquals(0, $this->getCountAutosaveEntries($entity_id));

    $page = $this->getSession()->getPage();

    $this->setFieldValue($page, 'field_tags[]', [[5, 'Drupal'], 'Tag2']);
    // Add Quote Paragraph.
    $this->addTextParagraph('field_paragraphs', 'Awesome quote', 'quote');

    $this->assertTrue($this->waitForAutosaveSubmits(1));

    $this->assertEquals([5, '$ID:Tag2'], $page->findField('field_tags[]')->getValue());

    $this->reloadPageAndRestore($entity_form_edit_url, $this->getLastAutosaveTimestamp($entity_id));

    $this->assertEquals([5, '$ID:Tag2'], $page->findField('field_tags[]')->getValue());

    // Save the article.
    $this->clickSave();

    // Check Quote paragraph.
    $this->assertSession()->pageTextContains('Awesome quote');
  }

}
