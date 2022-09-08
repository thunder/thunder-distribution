<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * Data producer LinkField test class.
 *
 * @group Thunder
 */
class LinkFieldTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create node type.
    $contentType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $contentType->save();

    // Create a single, and multi-value field.
    FieldStorageConfig::create([
      'field_name' => 'field_test_single',
      'entity_type' => 'node',
      'type' => 'link',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_single',
      'bundle' => 'article',
      'settings' => [
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_OPTIONAL,
      ],
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_test_multi',
      'entity_type' => 'node',
      'type' => 'link',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test_multi',
      'bundle' => 'article',
      'settings' => [
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_OPTIONAL,
      ],
    ])->save();

  }

  /**
   * Tests the LinkField data provider.
   */
  public function testLinkField() {
    $node = Node::create([
      'title' => 'Title1',
      'type' => 'article',
      'status' => 1,
      'field_test_single' => [
        'uri' => 'https://example.com',
        'title' => 'Example',
      ],
      'field_test_multi' => [
        [
          'uri' => 'https://example.com',
          'title' => 'Example',
        ],
        [
          'uri' => 'internal:/node',
          'title' => 'Node',
        ],
      ],
    ]);

    $node->save();

    // Single and multi-value fields are delivered differently to the producer.
    // A single value is still nested with 0 as key.
    // Example: [0] => ['uri' => 'https://example.com', 'title' => 'Example']
    //
    // A single value of a multi value field is not nested.
    // Example: ['uri' => 'https://example.com', 'title' => 'Example'].
    $fieldValues = $node->get('field_test_single')->getValue();
    $url = $this->executeDataProducer('link_field', [
      'field' => ($fieldValues),
      'property' => 'uri',
    ]);

    $this->assertEquals($fieldValues[0]['uri'], $url);

    $title = $this->executeDataProducer('link_field', [
      'field' => ($fieldValues),
      'property' => 'title',
    ]);

    $this->assertEquals($fieldValues[0]['title'], $title);

    foreach ($node->get('field_test_multi')->getValue() as $fieldValues) {
      $url = $this->executeDataProducer('link_field', [
        'field' => $fieldValues,
        'property' => 'uri',
      ]);

      // One of the test links is marked internal.
      $this->assertEquals(str_replace('internal:', '', $fieldValues['uri']), $url);

      $title = $this->executeDataProducer('link_field', [
        'field' => $fieldValues,
        'property' => 'title',
      ]);

      $this->assertEquals($fieldValues['title'], $title);
    }

  }

}
