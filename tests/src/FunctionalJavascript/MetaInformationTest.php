<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Testing of Meta Information.
 *
 * @group Thunder
 *
 * @todo Convert to functional test.
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
class MetaInformationTest extends ThunderJavascriptTestBase {

  use ThunderArticleTestTrait;
  use ThunderMetaTagTrait;
  use ThunderMediaTestTrait;
  use CronRunTrait;

  /**
   * Default user login role used for testing.
   *
   * @var string
   */
  protected static $defaultUserRole = 'administrator';

  /**
   * Meta tag configuration that will be set for Global meta tags.
   *
   * @var array
   */
  protected static $globalMetaTags = [
    'basic title' => 'Global Title',
    'basic keywords' => 'Thunder,CMS,Burda',
    'basic abstract' => '[random]',
    'basic description' => '[random]',
  ];

  /**
   * Meta tag configuration that will be set for Content meta tags.
   *
   * @var array
   */
  protected static $contentMetaTags = [
    'basic title' => '[node:title]',
    'basic abstract' => '[random]',
  ];

  /**
   * Meta tag configuration that will be set for Content->Article meta tags.
   *
   * @var array
   */
  protected static $articleMetaTags = [
    'basic title' => 'Test [node:field_teaser_text]',
    'basic description' => '[random]',
    'advanced robots' => 'index, follow, noydir',
    'advanced referrer' => 'no-referrer-when-downgrade',

    // OpenGraph Meta Tags.
    'open_graph og:image' => '[node:field_teaser_media:entity:field_image:facebook]',
    'open_graph og:image:type' => '[node:field_teaser_media:entity:field_image:facebook:mimetype]',
    'open_graph og:image:height' => '[node:field_teaser_media:entity:field_image:facebook:height]',
    'open_graph og:image:width' => '[node:field_teaser_media:entity:field_image:facebook:width]',
    'open_graph og:description' => '[node:field_teaser_text]',
    'open_graph og:title' => '[node:field_seo_title]',
    'open_graph og:site_name' => '[node:title]',
    'open_graph og:type' => 'article',

    // Schema.org metatags.
    'schema_article schema_article_headline' => '[node:field_seo_title]',
    'schema_article schema_article_description' => '[node:field_teaser_text]',

    // Facebook Metatags.
    'facebook fb:admins' => 'zuck',
    'facebook fb:pages' => 'some-fancy-fb-page-url',
    'facebook fb:app_id' => '1121151812167212,1121151812167213',
  ];

  /**
   * Custom meta tag configuration that will be set for Article meta tags.
   *
   * @var array
   */
  protected static $customMetaTags = [
    'basic title' => 'Custom [node:field_teaser_text]',
    'basic description' => '[random]',
    'advanced robots' => 'follow',
    'advanced referrer' => 'no-referrer',
    'schema_article schema_article_description' => 'I do my own description.',
  ];

  /**
   * List of Tokens that will be replaced with values.
   *
   * @var array
   */
  protected static $tokens = [
    '[node:field_seo_title]' => 'Test SEO Title',
    '[node:field_teaser_text]' => 'Test Teaser Text',
    '[node:title]' => 'Test Note Title',

    // For testing Media:1 is used for teaser.
    '[node:field_teaser_media:entity:field_image:facebook]' => 'LIKE:/files/styles/facebook/public/2016-05/thunder.jpg?',
    '[node:field_teaser_media:entity:field_image:facebook:mimetype]' => 'image/jpeg',
    '[node:field_teaser_media:entity:field_image:facebook:height]' => '630',
    '[node:field_teaser_media:entity:field_image:facebook:width]' => '1200',
  ];

  /**
   * Simple sitemap generator.
   *
   * @var \Drupal\simple_sitemap\Simplesitemap
   */
  protected $sitemapGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->sitemapGenerator = $this->container->get('simple_sitemap.generator');
  }

  /**
   * Set meta tag configuration for administration url.
   *
   * @param string $pageUrl
   *   Url to page where configuration should be set.
   * @param array $configuration
   *   List of configuration what will be set for meta tag.
   */
  protected function setMetaTagConfigurationForUrl(string $pageUrl, array $configuration): void {
    $this->drupalGet($pageUrl);

    $driver = $this->getSession()->getDriver();
    $this->expandAllTabs();
    $this->setFieldValues($this->generateMetaTagFieldValues($configuration));

    $this->scrollElementInView('[name="op"]');
    $driver->click('//input[@name="op"]');
  }

  /**
   * Create simple node for meta tag testing.
   *
   * @param string $contentType
   *   The node content type.
   * @param array $fieldValues
   *   Custom meta tag configuration for article.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createNodeWithFields(string $contentType, array $fieldValues = []): void {
    $term = $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845');
    $fieldValues += [
      'field_channel' => $term->id(),
      'title[0][value]' => static::$tokens['[node:title]'],
      'field_seo_title[0][value]' => static::$tokens['[node:field_seo_title]'],
      'field_teaser_text[0][value]' => static::$tokens['[node:field_teaser_text]'],
    ];

    $this->nodeFillNew($fieldValues, $contentType);

    $media = $this->loadMediaByUuid('17965877-27b2-428f-8b8c-7dccba9786e5');
    $this->selectMedia('field_teaser_media', 'image_browser', ['media:' . $media->id()]);

    $this->clickSave();
  }

  /**
   * Check saved configuration on meta tag overview page.
   *
   * @param string $configurationUrl
   *   Url to page where configuration should be set.
   * @param array $configuration
   *   List of configuration what will be set for meta tag.
   */
  protected function checkSavedConfiguration(string $configurationUrl, array $configuration): void {
    $this->drupalGet('admin/config/search/metatag');
    $page = $this->getSession()->getPage();

    $this->expandAllTabs();

    foreach ($configuration as $metaTagName => $metaTagValue) {
      $metaTag = explode(' ', $metaTagName);
      $fieldName = $this->getMetaTagFieldName($metaTag[1]);

      $this->assertNotEquals(
        NULL,
        $page->find(
          'xpath',
          '//tr[.//a[contains(@href, "/' . $configurationUrl . '")]]/td[1]//table//tr[./td[text()="' . $fieldName . ':"] and ./td[text()="' . $metaTagValue . '"]]'
        )
      );
    }
  }

  /**
   * Test Meta Tag default configuration and custom configuration for article.
   *
   * @dataProvider providerContentTypes
   */
  public function testArticleMetaTags(string $contentType): void {
    $globalConfigs = $this->generateMetaTagConfiguration([static::$globalMetaTags]);
    $contentConfigs = $this->generateMetaTagConfiguration([static::$contentMetaTags]);
    $articleConfigs = $this->generateMetaTagConfiguration([static::$articleMetaTags]);
    $customConfigs = $this->generateMetaTagConfiguration([static::$customMetaTags]);

    // Generate check configuration for default configuration.
    $checkArticleConfigs = $this->generateMetaTagConfiguration([
      $globalConfigs,
      $contentConfigs,
      $articleConfigs,
    ]);
    $checkArticleMetaTags = $this->replaceTokens($checkArticleConfigs, static::$tokens);

    // Generate check configuration for custom configuration.
    $checkCustomConfigs = $this->generateMetaTagConfiguration([
      $checkArticleConfigs,
      $customConfigs,
    ]);
    $checkCustomMetaTags = $this->replaceTokens($checkCustomConfigs, static::$tokens);

    // Edit Global configuration.
    $configurationUrl = 'admin/config/search/metatag/global';
    $this->setMetaTagConfigurationForUrl($configurationUrl, $globalConfigs);
    $this->checkSavedConfiguration($configurationUrl, $globalConfigs);

    // Edit Content configuration.
    $configurationUrl = 'admin/config/search/metatag/node';
    $this->setMetaTagConfigurationForUrl($configurationUrl, $contentConfigs);
    $this->checkSavedConfiguration($configurationUrl, $contentConfigs);

    // Edit Article configuration.
    $configurationUrl = 'admin/config/search/metatag/node__' . $contentType;
    $this->setMetaTagConfigurationForUrl($configurationUrl, $articleConfigs);
    $this->checkSavedConfiguration($configurationUrl, $articleConfigs);

    // Create Article with default meta tags and check it.
    $this->createNodeWithFields($contentType);
    $this->checkMetaTags($checkArticleMetaTags);

    // Create Article with custom meta tags and check it.
    $this->createNodeWithFields($contentType, $this->generateMetaTagFieldValues($checkCustomConfigs, 'field_meta_tags[0]'));
    $this->checkMetaTags($checkCustomMetaTags);
  }

  /**
   * Test Scheduling of Article.
   *
   * @dataProvider providerContentTypes
   */
  public function testArticleScheduling(string $contentType): void {
    $articleId = 10;

    // Create article with published 2 days ago, unpublish tomorrow.
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

    $this->createNodeWithFields($contentType, $fieldValues);

    // Check that Article is unpublished.
    $this->drupalGet('node/' . $articleId);
    $this->assertSession()
      ->elementExists('xpath', '//div[@id="content"]//article[contains(@class, "node--unpublished")]');

    $this->cronRun();

    // Check that Article is published.
    $this->drupalGet('node/' . $articleId);
    $this->assertSession()
      ->elementNotExists('xpath', '//div[@id="content"]//article[contains(@class, "node--unpublished")]');

    // Check that Article is published.
    $this->drupalGet('node/' . $articleId . '/edit');

    // Edit article and set un-publish date same as publish date.
    $unPublishDiffSeconds = 5;
    $unPublishTimestamp = strtotime("+{$unPublishDiffSeconds} seconds");
    $unPublishFieldValues = [
      'unpublish_on[0][value][date]' => date('Y-m-d', $unPublishTimestamp),
      'unpublish_on[0][value][time]' => date('H:i:s', $unPublishTimestamp),
      'unpublish_state[0]' => 'unpublished',
    ];

    $this->expandAllTabs();
    $this->setFieldValues($unPublishFieldValues);

    $this->clickSave();

    // Check that Article is published.
    $this->drupalGet('node/' . $articleId);
    $this->assertSession()
      ->elementNotExists('xpath', '//div[@id="content"]//article[contains(@class, "node--unpublished")]');

    // Wait sufficient time before cron is executed.
    sleep($unPublishDiffSeconds + 2);

    $this->cronRun();

    // Check that Article is unpublished.
    $this->drupalGet('node/' . $articleId);
    $this->assertSession()
      ->elementExists('xpath', '//div[@id="content"]//article[contains(@class, "node--unpublished")]');
  }

  /**
   * Get SiteMap dom elements by XPath.
   *
   * @param string $content
   *   XML string content of Site Map.
   * @param string $xpathQuery
   *   XPath to fetch elements from Site Map.
   *
   * @return \DOMNodeList
   *   Returns list of elements matching provided XPath.
   */
  public function getSiteMapDomElements(string $content, string $xpathQuery): \DOMNodeList {
    $domDoc = new \DOMDocument();
    $domDoc->loadXML($content);

    $xpath = new \DOMXpath($domDoc);
    $xpath->registerNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');

    return $xpath->query($xpathQuery);
  }

  /**
   * Test Site Map for node types.
   *
   * @dataProvider providerContentTypes
   * @group NoUpdate
   */
  public function testSiteMap(string $contentType): void {
    $articleId = 10;
    $articleUrl = 'test-sitemap-seo-title';

    $customFields = [
      'field_seo_title[0][value]' => $articleUrl,
    ];

    $this->createNodeWithFields($contentType, $customFields);

    $this->drupalGet('node/' . $articleId . '/edit');

    // Publish article.
    $this->setModerationState('published');
    $this->clickSave();

    // Do not add html transformation information to prevent rendering of the
    // sitemap in html.
    $this->sitemapGenerator->saveSetting('xsl', FALSE);
    $this->sitemapGenerator->generateSitemap('backend');

    $this->drupalGet($contentType . '/sitemap.xml');

    $content = $this->getSession()->getPage()->getContent();
    $domElements = $this->getSiteMapDomElements($content, '//sm:loc[contains(text(),"/' . $articleUrl . '")]/parent::sm:url/sm:priority');
    $this->assertEquals(1, $domElements->length);
    $this->assertEquals('0.5', $domElements->item(0)->nodeValue);

    // After sitemap.xml -> we have to open page without setting cookie before.
    $this->getSession()->visit($this->buildUrl('node/' . $articleId . '/edit'));

    $this->expandAllTabs();
    $this->setFieldValues([
      'priority_' . $contentType . '_node_settings' => '0.9',
    ]);

    $this->clickSave();

    $this->sitemapGenerator->generateSitemap('backend');
    $this->drupalGet($contentType . '/sitemap.xml');

    $content = $this->getSession()->getPage()->getContent();
    $domElements = $this->getSiteMapDomElements($content, '//sm:loc[contains(text(),"/' . $articleUrl . '")]/parent::sm:url/sm:priority');
    $this->assertEquals(1, $domElements->length);
    $this->assertEquals('0.9', $domElements->item(0)->nodeValue);

    // After sitemap.xml -> we have to open page without setting cookie before.
    $this->container->get('config.factory')
      ->getEditable('simple_sitemap.settings')
      ->set('max_links', 2)
      ->save();
    $this->sitemapGenerator->generateSitemap('backend');

    // Check loc, that it's pointing to sitemap.xml file.
    $this->drupalGet('sitemap.xml');
    $content = $this->getSession()->getPage()->getContent();
    $domElements = $this->getSiteMapDomElements($content, '(//sm:loc)[last()]');
    $lastSiteMapUrl = $domElements->item(0)->nodeValue;
    $page = ($contentType === 'article') ? 2 : 3;
    $this->assertStringEndsWith('sitemap.xml?page=' . $page, $lastSiteMapUrl);

    // Get 3rd sitemap.xml file and check that link exits there.
    $urlOptions = ['query' => ['page' => 3]];
    $this->getSession()
      ->visit($this->buildUrl($contentType . '/sitemap.xml', $urlOptions));
    $content = $this->getSession()->getPage()->getContent();
    $domElements = $this->getSiteMapDomElements($content, '//sm:loc[contains(text(),"/' . $articleUrl . '")]/parent::sm:url/sm:priority');
    $this->assertEquals(1, $domElements->length);
    $this->assertEquals('0.9', $domElements->item(0)->nodeValue);

    // After sitemap.xml -> we have to open page without setting cookie before.
    $this->getSession()->visit($this->buildUrl('node/' . $articleId . '/edit'));

    $this->expandAllTabs();
    $this->setFieldValues([
      'index_' . $contentType . '_node_settings' => '0',
    ]);

    $this->clickSave();

    $this->sitemapGenerator->generateSitemap('backend');
    $this->drupalGet($contentType . '/sitemap.xml', $urlOptions);

    // Depending on how many nodes are now in the sitemap, it should not exist
    // anymore, or it should not contain removed the node.
    $content = $this->getSession()->getPage()->getContent();
    if (str_contains($content, 'Generated by the Simple XML Sitemap Drupal module')) {
      $domElements = $this->getSiteMapDomElements($content, '//sm:loc[contains(text(),"/' . $articleUrl . '")]');
      $this->assertEquals(0, $domElements->length);
    }
    else {
      $this->assertSession()->responseContains('<title>404');
    }

    $this->getSession()->visit($this->buildUrl('node/' . $articleId . '/edit'));
  }

}
