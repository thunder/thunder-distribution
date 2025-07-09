<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;

/**
 * Test entities_with_term data producer.
 *
 * @group Thunder
 */
class EntitiesWithTermTest extends GraphQLTestBase {

  /**
   * The parent term.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected TermInterface $parent;

  /**
   * The taxonomy term reference field name.
   */
  protected const string TAXONOMY_FIELD_NAME = 'taxonomy_field';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'text',
    'taxonomy',
    'thunder_gqls',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');

    // Create vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'test1',
      'vid' => 'test1',
    ]);
    $vocabulary->save();

    // Create node type.
    $contentType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'display_submitted' => FALSE,
    ]);
    $contentType->save();

    // A parent term.
    $parent = Term::create([
      'name' => 'parent',
      'vid' => $vocabulary->id(),
    ]);
    $parent->save();

    // A child term to the parent above.
    $child = Term::create([
      'name' => 'child',
      'vid' => $vocabulary->id(),
      'parent' => $parent->id(),
    ]);
    $child->save();

    // Term field for article node type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => self::TAXONOMY_FIELD_NAME,
      'entity_type' => 'node',
      'translatable' => FALSE,
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'type' => 'entity_reference',
      'cardinality' => 1,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'entity_type' => 'node',
      'bundle' => 'article',
    ]);
    $field->save();

    // Some nodes to test with.
    $node1 = Node::create([
      'title' => 'Title1',
      'type' => 'article',
      'status' => 1,
      self::TAXONOMY_FIELD_NAME => $parent->id(),
    ]);
    $node1->save();

    $node2 = Node::create([
      'title' => 'Title2',
      'type' => 'article',
      'status' => 0,
      self::TAXONOMY_FIELD_NAME => $parent->id(),
    ]);
    $node2->save();

    $node3 = Node::create([
      'title' => 'Title3',
      'type' => 'article',
      'status' => 1,
      self::TAXONOMY_FIELD_NAME => $child->id(),
    ]);
    $node3->save();

    $this->parent = $parent;

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
      $this->builder->produce('entities_with_term')
        ->map('term', $this->builder->fromValue($this->parent))
        ->map('type', $this->builder->fromValue('node'))
        ->map('field', $this->builder->fromValue(self::TAXONOMY_FIELD_NAME))
    );
    $this->mockResolver('EntityList', 'total', $this->builder->fromValue(1));

    $metadata = $this->defaultCacheMetaData();
    $metadata->setCacheContexts(['user.permissions', 'user.node_grants:view']);
    $metadata->addCacheTags(['node_list', 'taxonomy_term:' . $this->parent->id()]);

    $this->assertResults($query, [], [
      'articles' => ['total' => '1'],
    ], $metadata);
  }

  /**
   * Tests the EntityWithTerms data provider.
   *
   * @param array $parameterMapping
   *   The mapped parameters for the data provider.
   * @param array $expectedResult
   *   The expected results.
   *
   * @covers \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntitiesWithTerm::resolve
   *
   * @dataProvider providerEntityWithTerms
   */
  public function testEntityWithTerms(array $parameterMapping, array $expectedResult): void {
    $parameterMapping['term'] = $this->parent;

    // Test producer without depth.
    /** @var \Drupal\thunder_gqls\Wrappers\EntityListResponse $result */
    $result = $this->executeDataProducer('entities_with_term', $parameterMapping);

    $this->assertNotNull($result);
    $this->assertEquals($expectedResult['total'], $result->total());
  }

  /**
   * Provides data for the testEntityWithTerms test.
   *
   * @return array[]
   *   An associative array of arrays, each having the following elements:
   *   - an array of mapped parameters.
   *   - the data provider values expected to be returned.
   *
   * @see ::testEntityWithTerms()
   */
  public static function providerEntityWithTerms() : array {
    return [
      'query without depth' => [
        [
          'type' => 'node',
          'bundles' => ['article'],
          'field' => self::TAXONOMY_FIELD_NAME,
          'offset' => 0,
          'limit' => 100,
          'conditions' => [],
          'languages' => [],
          'sortBy' => [],
          'depth' => 0,
        ],
        [
          'total' => 1,
        ],
      ],
      'query with depth=1' => [
        [
          'type' => 'node',
          'bundles' => ['article'],
          'field' => self::TAXONOMY_FIELD_NAME,
          'offset' => 0,
          'limit' => 100,
          'conditions' => [],
          'languages' => [],
          'sortBy' => [],
          'depth' => 1,
        ],
        [
          'total' => 2,
        ],
      ],
      'query with custom conditions' => [
        [
          'type' => 'node',
          'bundles' => ['article'],
          'field' => self::TAXONOMY_FIELD_NAME,
          'offset' => 0,
          'limit' => 100,
          'conditions' => [
            [
              'field' => 'title',
              'value' => 'Title%',
              'operator' => 'like',
            ],
            [
              'field' => 'status',
              'value' => [0, 1],
              'operator' => 'BETWEEN',
            ],
          ],
          'languages' => [],
          'sortBy' => [],
          'depth' => 0,
        ],
        [
          'total' => 2,
        ],
      ],
    ];
  }

}
