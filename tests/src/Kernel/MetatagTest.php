<?php

namespace Drupal\Tests\thunder\Kernel\Integration;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\thunder\Traits\ThunderKernelTestTrait;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Tests integration with the metatag module.
 *
 * @group Thunder
 */
class MetatagTest extends KernelTestBase {

  use ThunderKernelTestTrait;

  /**
   * The article node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'token',
    'media',
    'media_library',
    'media_library_media_modify',
    'views',
    'image',
    'file',
    'filter',
    'focal_point',
    'crop',
    'media_expire',
    'menu_ui',
    'scheduler',

    'metatag',
    'metatag_open_graph',
    'metatag_twitter_cards',
    'schema_metatag',
    'schema_article',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installConfig('system');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('crop');

    $this->installConfig(['metatag']);

    // Set site name.
    $this->config('system.site')->set('name', 'Test Site')->save();

    // Install focal point config for focal_point crop type.
    $this->container->get('config.installer')->installDefaultConfig('module', 'focal_point');
    $this->installThunderOptionalConfig();

    $mediaImage = $this->createSampleImageMedia();

    // Create sample node with the fields we need for metatag tokens.
    $this->node = Node::create([
      'title' => 'Title',
      'field_teaser_text' => 'The description',
      'type' => 'article',
      'field_seo_title' => 'SEO-title',
      'field_teaser_media' => [
        'target_id' => $mediaImage->id(),
      ],
    ]);

    $this->node->save();
  }

  /**
   * Tests default values as defined in metatag.metatag_defaults.node__article.
   */
  public function testTagDefaultValues(): void {
    $title = 'SEO-title';
    $description = 'The description';

    /** @var \Drupal\metatag\MetatagManager $metatagManager */
    $metatagManager = $this->container->get('metatag.manager');

    $tags = $metatagManager->tagsFromEntityWithDefaults($this->node);
    $elements = $metatagManager->generateRawElements($tags, $this->node);

    $this->assertStringEndsWith('/node/1', $elements['canonical_url']['#attributes']['href']);
    $this->assertEquals($description, $elements['description']['#attributes']['content']);
    $this->assertStringEndsWith('/files/image-test.png', $elements['image_src']['#attributes']['href']);
    $this->assertEquals('no-referrer', $elements['referrer']['#attributes']['content']);
    $this->assertEquals('index, follow', $elements['robots']['#attributes']['content']);
    $this->assertEquals($title, $elements['title']['#attributes']['content']);
    $this->assertEquals($description, $elements['og_description']['#attributes']['content']);
    $this->assertStringContainsString('/files/styles/facebook/public/image-test.png', $elements['og_image_0']['#attributes']['content']);
    $this->assertEquals('630', $elements['og_image_height']['#attributes']['content']);
    $this->assertEquals('1200', $elements['og_image_width']['#attributes']['content']);
    $this->assertEquals('image/webp', $elements['og_image_type']['#attributes']['content']);
    $this->assertEquals('Test Site', $elements['og_site_name']['#attributes']['content']);
    $this->assertEquals($title, $elements['og_title']['#attributes']['content']);
    $this->assertNotEmpty($elements['og_updated_time']['#attributes']['content']);
    $this->assertStringEndsWith('/node/1', $elements['og_url']['#attributes']['content']);

    $this->assertEquals($description, $elements['twitter_cards_description']['#attributes']['content']);
    $this->assertStringContainsString('/files/styles/twitter/public/image-test.png.webp', $elements['twitter_cards_image']['#attributes']['content']);
    $this->assertEquals('summary_large_image', $elements['twitter_cards_type']['#attributes']['content']);

    $this->assertEquals('Article', $elements['schema_article_type']['#attributes']['content']);
    $this->assertEquals($title, $elements['schema_article_headline']['#attributes']['content']);
    $this->assertEquals('Title', $elements['schema_article_name']['#attributes']['content']);
    $this->assertEquals($description, $elements['schema_article_description']['#attributes']['content']);
    $this->assertStringContainsString('/files/styles/facebook/public/image-test.png.webp', $elements['schema_article_image']['#attributes']['content']['url']);
    $this->assertEquals('Test Site', $elements['schema_article_publisher']['#attributes']['content']['name']);
    $this->assertEquals('Organization', $elements['schema_article_publisher']['#attributes']['content']['@type']);
  }

}
