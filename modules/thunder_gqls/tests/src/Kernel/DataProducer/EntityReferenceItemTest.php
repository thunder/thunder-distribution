<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityReferenceItem
 * @group Thunder
 */
class EntityReferenceItemTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
  ];

  /**
   * @covers ::resolve
   */
  public function testMultipleReturnsFullList(): void {
    $list = ['a', 'b', 'c'];
    $this->assertSame($list, $this->executeDataProducer('entity_reference_item', ['list' => $list, 'multiple' => TRUE]));
  }

  /**
   * @covers ::resolve
   */
  public function testSingleReturnsFirstItem(): void {
    $this->assertSame('a', $this->executeDataProducer('entity_reference_item', [
      'list' => ['a', 'b', 'c'],
      'multiple' => FALSE,
    ]));
  }

  /**
   * @covers ::resolve
   */
  public function testSingleOnEmptyListReturnsNull(): void {
    $this->assertNull($this->executeDataProducer('entity_reference_item', ['list' => [], 'multiple' => FALSE]));
  }

  /**
   * @covers ::resolve
   */
  public function testDefaultsToMultiple(): void {
    $list = ['a', 'b'];
    $this->assertSame($list, $this->executeDataProducer('entity_reference_item', ['list' => $list]));
  }

}
