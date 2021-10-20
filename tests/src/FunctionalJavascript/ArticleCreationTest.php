<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the article creation.
 *
 * @group Thunder
 */
class ArticleCreationTest extends ThunderJavascriptTestBase {

  use ThunderParagraphsTestTrait;
  use ThunderArticleTestTrait;
  use NodeCreationTrait;

  /**
   * Field name for paragraphs in article content.
   *
   * @var string
   */
  protected static $paragraphsField = 'field_paragraphs';

  /**
   * Test Creation of Article.
   */
  public function testCreateArticle() {
    // Create a video media item.
    $this->drupalGet("media/add/video");
    $this->assertSession()->fieldExists('Video URL')->setValue('https://www.youtube.com/watch?v=PWjcqE3QKBg');
    $this->assertSession()->fieldExists('Name')->setValue('Youtube');
    $this->assertSession()->buttonExists('Save')->press();

    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');
    $this->articleFillNew([
      'field_channel' => $term->id(),
      'title[0][value]' => 'Test article',
      'field_seo_title[0][value]' => 'Massive gaining seo traffic text',
    ]);

    $image1 = $this->loadMediaByUuid('23f6d444-ece1-465d-a667-b1fb80e641d3');
    $this->selectMedia('field_teaser_media', 'image_browser', ['media:' . $image1->id()]);

    // Add Image Paragraph.
    $this->addImageParagraph(static::$paragraphsField, ['media:' . $image1->id()]);

    // Add Text Paragraph.
    $this->addTextParagraph(static::$paragraphsField, '<p>Awesome text</p><p>With a new line</p>');

    // Add Gallery Paragraph between Image and Text.
    $image2 = $this->loadMediaByUuid('05048c57-942d-4251-ad12-ce562f8c79a0');
    $this->addGalleryParagraph(static::$paragraphsField, 'Test gallery', [
      'media:' . $image1->id(),
      'media:' . $image2->id(),
    ], 1);

    // Add Quote Paragraph.
    $this->addTextParagraph(static::$paragraphsField, 'Awesome quote', 'quote');

    // Add Twitter Paragraph between Text and Quote.
    $this->addSocialParagraph(static::$paragraphsField, 'https://twitter.com/ThunderCoreTeam/status/776417570756976640', 'twitter', 3);

    // Add Instagram Paragraph.
    $this->addSocialParagraph(static::$paragraphsField, 'https://www.instagram.com/p/B2huuS8AQVq/', 'instagram');

    // Add Link Paragraph.
    $this->addLinkParagraph(static::$paragraphsField, 'Link to Thunder', 'http://www.thunder.org');

    // Add Video paragraph at the beginning.
    $video = $this->getMediaByName('Youtube');
    $this->addVideoParagraph(static::$paragraphsField, ['media:' . $video->id()], 0);

    // Add Pinterest Paragraph.
    $this->addSocialParagraph(static::$paragraphsField, 'https://www.pinterest.de/pin/478085316687452268/', 'pinterest');

    $this->createScreenshot($this->getScreenshotFolder() . '/ArticleCreationTest_BeforeSave_' . date('Ymd_His') . '.png');

    $this->clickSave();

    $this->createScreenshot($this->getScreenshotFolder() . '/ArticleCreationTest_AfterSave_' . date('Ymd_His') . '.png');

    $this->assertPageTitle('Massive gaining seo traffic text');
    $this->assertSession()->pageTextContains('Test article');

    // Check Image paragraph.
    $this->assertSession()
      ->elementsCount('xpath', '//div[contains(@class, "field--name-field-paragraphs")]/div[contains(@class, "field__item")][2]//img', 1);

    // Check Text paragraph.
    $this->assertSession()->pageTextContains('Not an awesome text');

    // Check Gallery paragraph. Ensure that there are 2 images in gallery.
    $this->assertSession()
      ->elementsCount('xpath', '//div[contains(@class, "field--name-field-paragraphs")]/div[contains(@class, "field__item")][3]//div[contains(@class, "slick-track")]/div[not(contains(@class, "slick-cloned"))]//img', 2);

    // Check Quote paragraph.
    $this->assertSession()->pageTextContains('Awesome quote');

    // Check that one Instagram widget is on page.
    $this->getSession()
      ->wait(5000, "jQuery('iframe').filter(function(){return (this.src.indexOf('instagram.com/p/B2huuS8AQVq') !== -1);}).length === 1");
    $numOfElements = $this->getSession()->evaluateScript("jQuery('iframe').filter(function(){return (this.src.indexOf('instagram.com/p/B2huuS8AQVq') !== -1);}).length");
    $this->assertEquals(1, $numOfElements, "Number of instagrams on page should be one.");

    // Check that one Twitter widget is on page.
    $this->getSession()
      ->wait(5000, "jQuery('iframe').filter(function(){return (this.id.indexOf('twitter-widget-0') !== -1);}).length === 1");
    $numOfElements = $this->getSession()->evaluateScript("jQuery('iframe').filter(function(){return (this.id.indexOf('twitter-widget-0') !== -1);}).length");
    $this->assertEquals(1, $numOfElements, "Number of twitter on page should be one.");

    // Check Link Paragraph.
    $this->assertSession()->linkExists('Link to Thunder');
    $this->assertSession()->linkByHrefExists('http://www.thunder.org');

    // Check for sharing buttons.
    $this->assertSession()->elementExists('css', '.shariff-button.twitter');
    $this->assertSession()->elementExists('css', '.shariff-button.facebook');

    // Check Video paragraph.
    $this->getSession()
      ->wait(5000, "jQuery('iframe').filter(function(){return (this.src.indexOf('media/oembed?url=https%3A//www.youtube.com/watch%3Fv%3DPWjcqE3QKBg') !== -1);}).length === 1");
    $numOfElements = $this->getSession()->evaluateScript("jQuery('iframe').filter(function(){return (this.src.indexOf('/media/oembed?url=https%3A//www.youtube.com/watch%3Fv%3DPWjcqE3QKBg') !== -1);}).length");
    $this->assertEquals(1, $numOfElements, "Number of youtube on page should be one.");

    // Check that one Pinterest widget is on page.
    $this->assertSession()
      ->elementsCount('xpath', '//div[contains(@class, "field--name-field-paragraphs")]/div[contains(@class, "field__item")][9]//span[contains(@data-pin-id, "478085316687452268")]', 2);
  }

  /**
   * Test Creation of Article without content moderation.
   */
  public function testCreateArticleWithNoModeration() {
    // Delete all the articles so we can disable content moderation.
    foreach (\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'article']) as $node) {
      $node->delete();
    }
    \Drupal::service('module_installer')->uninstall(['thunder_workflow']);

    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');
    // Try to create an article.
    $this->articleFillNew([
      'field_channel' => $term->id(),
      'title[0][value]' => 'Test article',
      'field_seo_title[0][value]' => 'Massive gaining seo traffic text',
    ]);
    $this->clickSave();
    $this->assertPageTitle('Massive gaining seo traffic text');
    $this->assertSession()->pageTextContains('Test article');
  }

  /**
   * Tests draft creation and that reverting to the default revision works.
   */
  public function testModerationWorkflow() {
    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');
    $this->articleFillNew([
      'field_channel' => $term->id(),
      'title[0][value]' => 'Test workflow article',
      'field_seo_title[0][value]' => 'Massive gaining seo traffic text',
    ]);
    $this->setModerationState('published');
    $this->clickSave();
    $this->assertPageTitle('Massive gaining seo traffic text');

    $node = $this->getNodeByTitle('Test workflow article');

    $this->drupalGet($node->toUrl('edit-form'));

    $this->setModerationState('unpublished');
    $this->getSession()->getPage()->find('xpath', '//*[@id="edit-preview"]')->click();
    $this->clickLink('Back to content editing');
    $this->assertSession()->pageTextNotContains('An illegal choice has been detected. Please contact the site administrator.');

    $this->setFieldValues($this->getSession()->getPage(), [
      'title[0][value]' => 'Test workflow article in draft',
      'field_seo_title[0][value]' => 'Massive gaining even more seo traffic text',
    ]);
    $this->setModerationState('draft');
    $this->clickSave();

    $this->drupalGet($node->toUrl('edit-form'));

    $this->setFieldValues($this->getSession()->getPage(), [
      'title[0][value]' => 'Test workflow article in draft 2',
      'field_seo_title[0][value]' => 'Massive gaining even more and more seo traffic text',
    ]);
    $this->setModerationState('draft');
    $this->clickSave();

    $this->assertPageTitle('Massive gaining even more and more seo traffic text');

    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');

    $revert_url = Url::fromRoute('node.revision_revert_default_confirm', [
      'node' => $node->id(),
      'node_revision' => $node_storage->getLatestRevisionId($node->id()),
    ]);
    $this->drupalGet($revert_url);
    $this->submitForm([], $this->t('Revert'));

    $this->drupalGet($node->toUrl());
    $this->assertPageTitle('Massive gaining seo traffic text');

    $this->drupalGet($node->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('field_seo_title[0][value]', 'Massive gaining seo traffic text');
  }

}
