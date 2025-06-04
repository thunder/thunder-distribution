<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * Test entities_with_term data producer.
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
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $schema = <<<GQL
      type Query {
        articles: EntityList
      }
      type EntityList {
        total: Int!
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

}
