<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * Test thunder_entity_list data producer.
 *
 * @group Thunder
 */
class ThunderEntityListTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'text',
    'thunder_gqls',
  ];

  /**
   * Total number of nodes created for testing.
   */
  protected const int NODE_COUNT = 5;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');

    $contentType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'display_submitted' => FALSE,
    ]);
    $contentType->save();

    for ($i = 1; $i <= self::NODE_COUNT; $i++) {
      Node::create([
        'title' => 'Article ' . $i,
        'type' => 'article',
        'status' => 1,
      ])->save();
    }

    $schema = <<<GQL
    type Query {
      articles: EntityList
    }
    type EntityList {
      total: Int!
      hasNext: Boolean!
    }
GQL;

    $this->setUpSchema($schema);
  }

  /**
   * Test cache metadata for the query.
   */
  public function testQueryCacheMetadata(): void {
    $query = <<<GQL
      query {
        articles {
          total
        }
      }
GQL;

    $this->mockResolver('Query', 'articles',
      $this->builder->produce('thunder_entity_list')
        ->map('type', $this->builder->fromValue('node'))
    );
    $this->mockResolver('EntityList', 'total', $this->builder->fromValue(1));

    $metadata = $this->defaultCacheMetaData();
    $metadata->setCacheContexts(['user.permissions', 'user.node_grants:view']);
    $metadata->addCacheTags(['node_list']);

    $this->assertResults($query, [], [
      'articles' => ['total' => '1'],
    ], $metadata);
  }

  /**
   * Tests hasNext is TRUE when more items exist beyond the current page.
   */
  public function testHasNextReturnsTrueWhenMoreItemsExist(): void {
    $query = <<<GQL
      query {
        articles {
          hasNext
        }
      }
GQL;

    $this->mockResolver('Query', 'articles',
      $this->builder->produce('thunder_entity_list')
        ->map('type', $this->builder->fromValue('node'))
        ->map('offset', $this->builder->fromValue(0))
        ->map('limit', $this->builder->fromValue(3))
    );
    $this->mockResolver('EntityList', 'hasNext',
      $this->builder->produce('thunder_entity_list_has_next')
        ->map('list', $this->builder->fromParent())
    );

    $metadata = $this->defaultCacheMetaData();
    $metadata->setCacheContexts(['user.permissions', 'user.node_grants:view']);
    $metadata->addCacheTags(['node_list']);

    $this->assertResults($query, [], [
      'articles' => ['hasNext' => TRUE],
    ], $metadata);
  }

  /**
   * Tests hasNext is FALSE when the page covers all remaining items.
   */
  public function testHasNextReturnsFalseWhenNoMoreItemsExist(): void {
    $query = <<<GQL
      query {
        articles {
          hasNext
        }
      }
GQL;

    $this->mockResolver('Query', 'articles',
      $this->builder->produce('thunder_entity_list')
        ->map('type', $this->builder->fromValue('node'))
        ->map('offset', $this->builder->fromValue(0))
        ->map('limit', $this->builder->fromValue(self::NODE_COUNT))
    );
    $this->mockResolver('EntityList', 'hasNext',
      $this->builder->produce('thunder_entity_list_has_next')
        ->map('list', $this->builder->fromParent())
    );

    $metadata = $this->defaultCacheMetaData();
    $metadata->setCacheContexts(['user.permissions', 'user.node_grants:view']);
    $metadata->addCacheTags(['node_list']);

    $this->assertResults($query, [], [
      'articles' => ['hasNext' => FALSE],
    ], $metadata);
  }

}
