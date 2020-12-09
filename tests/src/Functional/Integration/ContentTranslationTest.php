<?php

namespace Drupal\Tests\thunder\Functional\Integration;

use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\thunder\Functional\ThunderTestBase;

/**
 * Tests integration with the content_translation.
 *
 * @group Thunder
 */
class ContentTranslationTest extends ThunderTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_testing_demo',
    'thunder_workflow',
    'thunder_translation',
  ];

  /**
   * List of used languages.
   *
   * @var \Drupal\language\ConfigurableLanguageInterface[]
   */
  protected $languages = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->languages['en'] = ConfigurableLanguage::createFromLangcode('en');
    $this->languages['de'] = ConfigurableLanguage::createFromLangcode('de');
    $this->languages['de']->save();
  }

  /**
   * Test that basic translation creation works.
   */
  public function testBasicContentTranslation() {

    $this->logWithRole('editor');

    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/article');
    $page->selectFieldOption('Channel', 'News');
    $page->fillField('Title', 'English draft');
    $page->fillField('SEO Title', 'English draft');

    $page->pressButton('Save');

    $node = $this->getNodeByTitle('English draft');

    $url = $node->toUrl('drupal:content-translation-add');
    $url->setRouteParameter('source', 'en');
    $url->setRouteParameter('target', 'de');

    $this->drupalGet($url);
    $page->fillField('Title', 'German draft');
    $page->pressButton('Save');
  }

  /**
   * Test the field translatable property for all field configs.
   */
  public function testFieldTranslationKey() {
    $whitelist = [
      'field.field.media.instagram.field_url',
      'field.field.media.pinterest.field_url',
      'field.field.media.twitter.field_author',
      'field.field.media.twitter.field_content',
      'field.field.media.twitter.field_url',
    ];
    foreach (FieldConfig::loadMultiple() as $field) {
      if (in_array($field->getConfigDependencyName(), $whitelist)) {
        continue;
      }
      if (in_array($field->getType(), [
        'entity_reference',
        'entity_reference_revisions',
        'datetime',
        'image',
      ])) {
        $this->assertFalse($field->isTranslatable(), sprintf('%s is translatable.', $field->getConfigDependencyName()));
      }
      else {
        $this->assertTrue($field->isTranslatable(), sprintf('%s is not translatable.', $field->getConfigDependencyName()));
      }
    }
  }

}
