<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Unit\DataProducer;

use Drupal\Tests\UnitTestCase;
use Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityReferenceItem;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityReferenceItem
 * @group Thunder
 */
class EntityReferenceItemTest extends UnitTestCase {

  protected EntityReferenceItem $producer;

  protected function setUp(): void {
    parent::setUp();
    $this->producer = new EntityReferenceItem([], 'entity_reference_item', []);
  }

  /**
   * @covers ::resolve
   */
  public function testMultipleReturnsFullList(): void {
    $list = ['a', 'b', 'c'];
    $this->assertSame($list, $this->producer->resolve($list, TRUE));
  }

  /**
   * @covers ::resolve
   */
  public function testSingleReturnsFirstItem(): void {
    $this->assertSame('a', $this->producer->resolve(['a', 'b', 'c'], FALSE));
  }

  /**
   * @covers ::resolve
   */
  public function testSingleOnEmptyListReturnsNull(): void {
    $this->assertNull($this->producer->resolve([], FALSE));
  }

  /**
   * @covers ::resolve
   */
  public function testDefaultsToMultiple(): void {
    $list = ['a', 'b'];
    $this->assertSame($list, $this->producer->resolve($list));
  }

}
