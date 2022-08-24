<?php

namespace Drupal\Tests\thunder\FunctionalJavascript\Integration;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;
use Drupal\Tests\paragraphs_paste\FunctionalJavascript\ParagraphsPasteJavascriptTestTrait;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderArticleTestTrait;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderJavascriptTestBase;
use Drupal\Tests\thunder\FunctionalJavascript\ThunderParagraphsTestTrait;

/**
 * Tests the creation of paragraphs by pasting random data.
 *
 * @group Thunder
 */
class ParagraphsPasteTest extends ThunderJavascriptTestBase {

  use LoginAdminTrait;
  use ParagraphsPasteJavascriptTestTrait;
  use ThunderParagraphsTestTrait;
  use ThunderArticleTestTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_paragraphs_paste',
    'thunder_testing_demo',
    'thunder_workflow',
    'thunder_test_mock_request',
  ];

  /**
   * Field name for paragraphs in article content.
   *
   * @var string
   */
  protected static $paragraphsField = 'field_paragraphs';

  /**
   * Test paste functionality.
   */
  public function testPaste(): void {
    $paragraphsField = static::$paragraphsField;
    $text = 'Lorem ipsum dolor sit amet.';
    $this->drupalGet("node/add/article");
    usleep(50000);
    $this->assertTrue($this->getSession()->getDriver()->isVisible('//*[@data-paragraphs-paste-target="field_paragraphs"]'), 'Paragraphs Paste should be visible.');

    $this->scrollElementInView("[data-paragraphs-paste-target=\"" . static::$paragraphsField . "\"]");
    $this->simulatePasteEvent($paragraphsField, $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]', 10000, 'Text field in paragraph form should be present.');
    $this->assertEquals(sprintf('<p>%s</p>', $text), $this->getSession()->getPage()->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into paragraph subform.');
  }

  /**
   * Test multiline text with video functionality.
   */
  public function testMultilineTextPaste(): void {
    $text = [
      'Spicy jalapeno bacon ipsum dolor amet short ribs ribeye chislic, turkey shank chuck cupim bacon bresaola.',
      'https://www.youtube.com/watch?v=PWjcqE3QKBg',
      'Picanha porchetta cupim, salami jerky alcatra doner strip steak pork loin short loin pork belly tail ham hock cow shoulder.',
    ];
    $text = implode('\n\n\n', $text);
    $this->drupalGet("node/add/article");
    usleep(50000);
    $this->assertTrue($this->getSession()->getDriver()->isVisible('//*[@data-paragraphs-paste-target="field_paragraphs"]'), 'Paragraphs Paste should be visible.');

    $this->scrollElementInView("[data-paragraphs-paste-target=\"" . static::$paragraphsField . "\"]");
    $this->simulatePasteEvent(static::$paragraphsField, $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]', 10000, 'Text field in paragraph form should be present.');
    $this->assertEquals(sprintf('<p>%s</p>', "Spicy jalapeno bacon ipsum dolor amet short ribs ribeye chislic, turkey shank chuck cupim bacon bresaola."), $this->getSession()->getPage()->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into paragraph subform.');
    $this->assertEquals("media:22", $this->getSession()->getPage()->find('xpath', '//input[@name="field_paragraphs[1][subform][field_video][target_id]"]')->getValue(), 'Video should be connected to the paragraph subform.');
    $this->assertEquals(sprintf('<p>%s</p>', "Picanha porchetta cupim, salami jerky alcatra doner strip steak pork loin short loin pork belly tail ham hock cow shoulder."), $this->getSession()->getPage()->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-2-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into paragraph subform.');
  }

  /**
   * Verify that the paste area stays after a first paste.
   */
  public function testPastingTwice(): void {
    $this->testPaste();

    $text = 'Bacon ipsum dolor amet cow picanha andouille strip steak tongue..';
    // Wait for scrollHeight to update.
    sleep(1);
    $this->scrollElementInView("[data-paragraphs-paste-target=\"" . static::$paragraphsField . "\"]");
    $this->simulatePasteEvent(static::$paragraphsField, $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-paragraphs-1-subform-field-text-0-value"]', 10000, 'Text field in paragraph form should be present.');
    $this->assertEquals(sprintf('<p>%s</p>', $text), $this->getSession()->getPage()->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-1-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into paragraph subform.');
  }

  /**
   * Test paste functionality with two paste areas in the form.
   */
  public function testPastingInTwoAreas(): void {
    $content_type = 'article';

    $field_name = 'field_second_paragraphs';
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => ['target_type' => 'paragraph'],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $content_type,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = EntityFormDisplay::load("node.$content_type.default");
    $display->setComponent($field_name, $display->getComponent('field_paragraphs'));
    $display->save();

    // Check that paste functionality is working with default config.
    $this->drupalGet("node/add/$content_type");

    $this->assertTrue($this->getSession()->getDriver()->isVisible('//*[@data-paragraphs-paste-target="field_paragraphs"]'), 'Paragraphs Paste area should be visible.');
    $this->assertTrue($this->getSession()->getDriver()->isVisible('//*[@data-paragraphs-paste-target="field_second_paragraphs"]'), 'Second Paragraphs Paste area should be visible.');
    $text = 'Lorem ipsum dolor sit amet.';
    $this->scrollElementInView("[data-paragraphs-paste-target=\"" . static::$paragraphsField . "\"]");
    $this->simulatePasteEvent(static::$paragraphsField, $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]', 10000, 'Text field in paragraph form should be present.');
    $this->assertEquals(sprintf('<p>%s</p>', $text), $this->getSession()->getPage()->find('xpath', '//textarea[@data-drupal-selector="edit-field-paragraphs-0-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into paragraph subform.');

    $text = 'Bacon ipsum dolor amet cow picanha andouille strip steak tongue..';
    // Wait for scrollHeight to update.
    sleep(1);
    $this->scrollElementInView("[data-paragraphs-paste-target=\"{$field_name}\"]");
    $this->simulatePasteEvent($field_name, $text);
    $this->waitForElementPresent('[data-drupal-selector="edit-field-second-paragraphs-0-subform-field-text-0-value"]', 10000, 'Text field in second paragraph form should be present.');
    $this->assertEquals(sprintf('<p>%s</p>', $text), $this->getSession()->getPage()->find('xpath', '//textarea[@data-drupal-selector="edit-field-second-paragraphs-0-subform-field-text-0-value"]')->getValue(), 'Text should be pasted into the second paragraph subform.');
  }

}
