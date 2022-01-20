<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Data producers Metatags test class.
 *
 * @group Thunder
 */
class ThunderMetatagsTest extends GraphQLTestBase {

  use TestFileCreationTrait;

  /**
   * The article node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'token',
    'media',
    'image',
    'file',
    'focal_point',
    'crop',
    'media_expire',
    'entity_reference',
    'metatag',
    'metatag_open_graph',
    'metatag_twitter_cards',
    'schema_metatag',
    'schema_article',
    'menu_ui',
    'scheduler',
    'thunder_gqls',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installConfig(['metatag']);
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installEntitySchema('crop');
    $this->installSchema('file', 'file_usage');

    /** @var \Drupal\Core\Config\ConfigInstallerInterface $configInstaller */
    $configInstaller = $this->container->get('config.installer');

    // Install Thunder optional config.
    $extension_path = $this->container->get('extension.list.profile')->getPath('thunder');
    $optional_install_path = $extension_path . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
    $storage = new FileStorage($optional_install_path);
    $configInstaller->installOptionalConfig($storage);

    // Install focal point config.
    $configInstaller->installDefaultConfig('module', 'focal_point');

    // Create a sample media.
    $imageFile = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $imageFile->save();

    $mediaImage = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_image' => [
        [
          'target_id' => $imageFile->id(),
          'alt' => 'Alt text',
          'title' => 'Title text',
        ],
      ],
    ]);
    $mediaImage->save();

    $this->node = Node::create([
      'title' => 'Title',
      'field_teaser_text' => 'The description',
      'type' => 'article',
      'field_seo_title' => 'SEO-title',
      'field_teaser_media' => [
        [
          'target_id' => $mediaImage->id(),
        ],
      ],
    ]);

    $this->node->save();
  }

  /**
   * @covers \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityLinks::resolve
   */
  public function testThunderMetatag(): void {
    $title = 'SEO-title';
    $description = 'The description';

    $results = $this->executeDataProducer('thunder_metatags', [
      'value' => $this->node,
    ]);

    $this->assertNotEmpty($results);

    $tags = [];
    foreach ($results as $result) {
      $attributes = Json::decode($result['attributes']);
      $key = '';
      foreach (['name', 'property', 'rel', 'http-equiv'] as $nameAttribute) {
        if (!empty($attributes[$nameAttribute])) {
          $key = $attributes[$nameAttribute];
          break;
        }
      }

      $this->assertNotEmpty($key);
      $tags[$key] = $attributes;
    }

    $this->assertStringEndsWith('/node/1', $tags['canonical']['href']);
    $this->assertEquals('en', $tags['content-language']['content']);
    $this->assertEquals($description, $tags['description']['content']);
    $this->assertStringEndsWith('/files/image-test.png', $tags['image_src']['href']);
    $this->assertEquals('no-referrer', $tags['referrer']['content']);
    $this->assertEquals($title, $tags['title']['content']);
    $this->assertEquals('index, follow', $tags['robots']['content']);
    $this->assertEquals($description, $tags['og:description']['content']);
    $this->assertEquals($title, $tags['og:title']['content']);
    $this->assertStringContainsString('/files/styles/facebook/public/image-test.png', $tags['og:image']['content']);
    $this->assertEquals('image/png', $tags['og:image:type']['content']);
    $this->assertEquals('1200', $tags['og:image:width']['content']);
    $this->assertEquals('630', $tags['og:image:height']['content']);
    $this->assertNotEmpty($tags['og:updated_time']['content']);
    $this->assertStringEndsWith('/node/1', $tags['og:url']['content']);
    $this->assertEquals('summary_large_image', $tags['twitter:card']['content']);
    $this->assertEquals($description, $tags['twitter:description']['content']);
    $this->assertStringContainsString('/files/styles/twitter/public/image-test.png', $tags['twitter:image']['content']);
    $this->assertEquals('1024', $tags['twitter:image:width']['content']);
    $this->assertEquals('512', $tags['twitter:image:height']['content']);
  }

}
