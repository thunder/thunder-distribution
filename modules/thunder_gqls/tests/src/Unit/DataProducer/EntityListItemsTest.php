<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Unit\DataProducer;

use Drupal\Tests\UnitTestCase;
use Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityListItems;
use Drupal\thunder_gqls\Wrappers\EntityListResponseInterface;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityListItems
 * @group Thunder
 */
class EntityListItemsTest extends UnitTestCase {

  /**
   * The data producer under test.
   *
   * @var \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityListItems
   */
  protected EntityListItems $producer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->producer = new EntityListItems([], 'entity_list_items', []);
  }

  /**
   * @covers ::resolve
   */
  public function testReturnsItemsFromList(): void {
    $items = ['a', 'b', 'c'];
    $list = $this->createMock(EntityListResponseInterface::class);
    $list->method('items')->willReturn($items);
    $this->assertSame($items, $this->producer->resolve($list));
  }

  /**
   * @covers ::resolve
   */
  public function testReturnsEmptyItems(): void {
    $list = $this->createMock(EntityListResponseInterface::class);
    $list->method('items')->willReturn([]);
    $this->assertSame([], $this->producer->resolve($list));
  }

}
