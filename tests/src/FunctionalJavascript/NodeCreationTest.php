<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the article creation.
 *
 * @group Thunder
 */
class NodeCreationTest extends ThunderJavascriptTestBase {

  use ThunderParagraphsTestTrait;
  use ThunderArticleTestTrait;
  use NodeCreationTrait;

  /**
   * Field name for paragraphs in node content.
   *
   * @var string
   */
  protected static string $paragraphsField = 'field_paragraphs';

  /**
   * Test Creation of nodes.
   *
   * @dataProvider providerContentTypes
   */
  public function testCreateNode(string $contentType, string $contentTypeDisplayName): void {
    // Create a video media item.
    $this->drupalGet("media/add/video");
    $this->assertSession()->fieldExists('Video URL')->setValue('https://www.youtube.com/watch?v=PWjcqE3QKBg');
    $this->assertSession()->fieldExists('Name')->setValue('Youtube');
    $this->assertSession()->buttonExists('Save')->press();

    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');
    $this->nodeFillNew([
      'field_channel' => $term->id(),
      'title[0][value]' => 'Test ' . $contentTypeDisplayName,
      'field_seo_title[0][value]' => 'Massive gaining seo traffic text',
    ], $contentType);

    $image1 = $this->loadMediaByUuid('23f6d444-ece1-465d-a667-b1fb80e641d3');
    $this->selectMedia('field_teaser_media', [$image1->id()]);

    // Add Image Paragraph.
    $this->addImageParagraph(static::$paragraphsField, [$image1->id()]);

    // Add Text Paragraph.
    $this->addTextParagraph(static::$paragraphsField, '<p>Awesome text</p><p>With a new line</p>');

    // Add Gallery Paragraph between Image and Text.
    $image2 = $this->loadMediaByUuid('05048c57-942d-4251-ad12-ce562f8c79a0');
    $this->addGalleryParagraph(static::$paragraphsField, 'Test gallery', [
      $image1->id(),
      $image2->id(),
    ], 1);

    // Add Quote Paragraph.
    $this->addTextParagraph(static::$paragraphsField, 'Awesome quote', 'quote');

    // Add Twitter Paragraph between Text and Quote.
    $this->addSocialParagraph(static::$paragraphsField, 'https://twitter.com/ThunderCoreTeam/status/776417570756976640', 'twitter', 3);

    // Add Link Paragraph.
    $this->addLinkParagraph(static::$paragraphsField, 'Link to Thunder', 'http://www.thunder.org');

    // Add Video paragraph at the beginning.
    $video = $this->getMediaByName('Youtube');
    $this->addVideoParagraph(static::$paragraphsField, [$video->id()], 0);

    // Add Pinterest Paragraph.
    $this->addSocialParagraph(static::$paragraphsField, 'https://www.pinterest.de/pin/478085316687452268/', 'pinterest');

    $this->createScreenshot($this->getScreenshotFolder() . '/' . ucfirst($contentType) . 'CreationTest_BeforeSave_' . date('Ymd_His') . '.png');

    $this->clickSave();

    $this->createScreenshot($this->getScreenshotFolder() . '/' . ucfirst($contentType) . 'CreationTest_AfterSave_' . date('Ymd_His') . '.png');

    $this->assertPageTitle('Massive gaining seo traffic text');
    $this->assertSession()->pageTextContains('Test ' . $contentTypeDisplayName);

    // Check Image paragraph.
    $this->assertSession()
      ->elementsCount('xpath', '//div[contains(@class, "field--name-field-paragraphs")]/div[contains(@class, "field__item")][2]//img', 1);

    // Check Text paragraph.
    $this->assertSession()->pageTextContains('Awesome text');

    // Check Gallery paragraph. Ensure that there are 2 images in gallery.
    $this->assertEquals(
      2,
      $this->getSession()->evaluateScript('document.querySelectorAll("div.field--name-field-media-images div.field__item img").length'),
      'There should be two images shown in frontend.'
    );

    // Check Quote paragraph.
    $this->assertSession()->pageTextContains('Awesome quote');

    // Check that one Twitter widget is on page.
    $this->getSession()
      ->wait(5000, "jQuery('iframe').filter(function(){return (this.id.indexOf('twitter-widget-0') !== -1);}).length === 1");
    $numOfElements = $this->getSession()->evaluateScript("jQuery('iframe').filter(function(){return (this.id.indexOf('twitter-widget-0') !== -1);}).length");
    $this->assertEquals(1, $numOfElements, "Number of twitter on page should be one.");

    // Check Link Paragraph.
    $this->assertSession()->linkExists('Link to Thunder');
    $this->assertSession()->linkByHrefExists('http://www.thunder.org');

    // Check Video paragraph.
    $this->getSession()
      ->wait(5000, "jQuery('iframe').filter(function(){return (this.src.indexOf('media/oembed?url=https%3A//www.youtube.com/watch%3Fv%3DPWjcqE3QKBg') !== -1);}).length === 1");
    $numOfElements = $this->getSession()->evaluateScript("jQuery('iframe').filter(function(){return (this.src.indexOf('/media/oembed?url=https%3A//www.youtube.com/watch%3Fv%3DPWjcqE3QKBg') !== -1);}).length");
    $this->assertEquals(1, $numOfElements, "Number of youtube on page should be one.");

    // Check that one Pinterest widget is on page.
    $this->assertSession()
      ->elementsCount('xpath', '//div[contains(@class, "field--name-field-paragraphs")]/div[contains(@class, "field__item")][8]//span[contains(@data-pin-id, "478085316687452268")]', 2);
  }

}
