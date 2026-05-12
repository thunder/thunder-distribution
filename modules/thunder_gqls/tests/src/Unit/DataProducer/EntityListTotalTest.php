<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Unit\DataProducer;

use Drupal\Tests\UnitTestCase;
use Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityListTotal;
use Drupal\thunder_gqls\Wrappers\EntityListResponseInterface;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityListTotal
 * @group Thunder
 */
class EntityListTotalTest extends UnitTestCase {

  protected EntityListTotal $producer;

  protected function setUp(): void {
    parent::setUp();
    $this->producer = new EntityListTotal([], 'entity_list_total', []);
  }

  /**
   * @covers ::resolve
   */
  public function testReturnsTotalFromList(): void {
    $list = $this->createMock(EntityListResponseInterface::class);
    $list->method('total')->willReturn(42);
    $this->assertSame(42, $this->producer->resolve($list));
  }

  /**
   * @covers ::resolve
   */
  public function testReturnsZeroTotal(): void {
    $list = $this->createMock(EntityListResponseInterface::class);
    $list->method('total')->willReturn(0);
    $this->assertSame(0, $this->producer->resolve($list));
  }

}
