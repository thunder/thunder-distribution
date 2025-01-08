<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;

/**
 * Test entities_with_term data producer.
 *
 * @group Thunder
 */
class ThunderSearchApiTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'search_api',
    'search_api_test',
    'thunder_gqls',
  ];

  /**
   * The search server used for testing.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $searchApiServer;

  /**
   * The search index used for testing.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $searchApiIndex;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installSchema('search_api', [
      'search_api_item',
    ]);
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');

    // Create a test server.
    $this->searchApiServer = Server::create([
      'name' => 'Test Server',
      'id' => 'test_server',
      'status' => 1,
      'backend' => 'search_api_test',
    ]);
    $this->searchApiServer->save();

    $this->searchApiIndex = Index::create([
      'name' => 'Test Index',
      'id' => 'test_index',
      'status' => 1,
      'tracker_settings' => [
        'default' => [],
      ],
      'datasource_settings' => [
        'entity:node' => [],
      ],
      'server' => $this->searchApiServer->id(),
      'options' => ['index_directly' => FALSE],
    ]);
    $this->searchApiIndex->save();

    $schema = <<<GQL
      type Query {
        search: SearchApiResult
      }
      type SearchApiResult {
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
        search {
          total
        }
      }
GQL;

    $this->mockResolver('Query', 'search',
      $this->builder->produce('thunder_search_api')
        ->map('index', $this->builder->fromValue('test_index'))
        ->map('offset', $this->builder->fromValue(0))
        ->map('limit', $this->builder->fromValue(20))
    );
    $this->mockResolver('SearchApiResult', 'total', $this->builder->fromValue(1));

    $metadata = $this->defaultCacheMetaData();
    $metadata->setCacheContexts(['languages:language_interface', 'user.permissions', 'user.node_grants:view']);
    $metadata->addCacheTags(['config:search_api.index.test_index', 'node_list']);

    $this->assertResults($query, [], [
      'search' => ['total' => '1'],
    ], $metadata);
  }

}
