<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

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
  protected NodeInterface $node;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'token',
    'metatag',
    'metatag_open_graph',
    'metatag_twitter_cards',
    'schema_metatag',
    'schema_article',
    'thunder_gqls',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installConfig(['metatag']);

    // Set site name.
    $this->config('system.site')->set('name', 'Test Site')->save();

    $this->node = Node::create([
      'title' => 'Title',
      'type' => 'article',
    ]);

    $this->node->save();
  }

  /**
   * @covers \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\MetaTags::resolve
   */
  public function testThunderMetatag(): void {
    $results = $this->executeDataProducer('thunder_metatags', [
      'value' => $this->node,
    ]);

    $this->assertNotEmpty($results);

    $this->assertStringEndsWith('\/node\/1"}', $results[1]['attributes']);
    $this->assertEquals('{"name":"title","content":"Title | Test Site"}', $results[0]['attributes']);
  }

}
