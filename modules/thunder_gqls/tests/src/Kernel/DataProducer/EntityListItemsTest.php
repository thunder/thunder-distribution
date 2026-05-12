<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\thunder_gqls\Wrappers\EntityListResponseInterface;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityListItems
 * @group Thunder
 */
class EntityListItemsTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
  ];

  /**
   * @covers ::resolve
   */
  public function testReturnsItemsFromList(): void {
    $items = ['a', 'b', 'c'];
    $list = $this->createMock(EntityListResponseInterface::class);
    $list->method('items')->willReturn($items);
    $this->assertSame($items, $this->executeDataProducer('entity_list_items', ['list' => $list]));
  }

  /**
   * @covers ::resolve
   */
  public function testReturnsEmptyItems(): void {
    $list = $this->createMock(EntityListResponseInterface::class);
    $list->method('items')->willReturn([]);
    $this->assertSame([], $this->executeDataProducer('entity_list_items', ['list' => $list]));
  }

}
