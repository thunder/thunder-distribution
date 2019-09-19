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
    'content_moderation',
    'content_translation',
  ];

  /**
   * List of used languages.
   *
   * @var \Drupal\Core\Language\LanguageInterface[]
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
      'field.field.media.twitter.field_author',
      'field.field.media.twitter.field_content',
    ];
    foreach (FieldConfig::loadMultiple() as $field) {
      if (in_array($field->getConfigDependencyName(), $whitelist)) {
        continue;
      }
      else {
        if (in_array($field->getType(), [
          'entity_reference',
          'entity_reference_revisions',
          'datetime',
          'image',
          'link',
        ])) {
          $this->assertFalse($field->isTranslatable(), sprintf('%s is translatable.', $field->getConfigDependencyName()));
        }
        else {
          $this->assertTrue($field->isTranslatable(), sprintf('%s is not translatable.', $field->getConfigDependencyName()));
        }
      }
    }
  }

}
