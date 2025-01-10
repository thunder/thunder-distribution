<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\node\NodeInterface;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Data producers EntityLinks test class.
 *
 * @group Thunder
 */
class EntityLinksTest extends GraphQLTestBase {

  /**
   * The node entity.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected NodeInterface $node;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $contentType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'display_submitted' => FALSE,
    ]);
    $contentType->save();

    $this->node = Node::create([
      'title' => 'Title',
      'type' => 'article',
    ]);

    $this->node->save();
  }

  /**
   * @covers \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityLinks::resolve
   */
  public function testEntityLinks(): void {
    $result = $this->executeDataProducer('entity_links', [
      'entity' => $this->node,
    ]);

    $this->assertNotNull($result);
    $this->assertEmpty($result['editForm'], 'Edit form link is not available without user permission.');

    $this->setUpCurrentUser([], array_merge(
      $this->userPermissions(),
      ['edit any ' . $this->node->getType() . ' content']
    ));

    $result = $this->executeDataProducer('entity_links', [
      'entity' => $this->node,
    ]);

    $this->assertNotNull($result);
    $this->assertEquals('/node/1/edit', $result['editForm'], 'With edit permission, the edit form link is exposed.');
  }

}
