<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\thunder_gqls\Wrappers\EntityListResponseInterface;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ThunderEntityListTotal
 * @group Thunder
 */
class ThunderEntityListTotalTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
  ];

  /**
   * @covers ::resolve
   */
  public function testReturnsTotalFromList(): void {
    $list = $this->createMock(EntityListResponseInterface::class);
    $list->method('total')->willReturn(42);
    $this->assertSame(42, $this->executeDataProducer('thunder_entity_list_total', ['list' => $list]));
  }

  /**
   * @covers ::resolve
   */
  public function testReturnsZeroTotal(): void {
    $list = $this->createMock(EntityListResponseInterface::class);
    $list->method('total')->willReturn(0);
    $this->assertSame(0, $this->executeDataProducer('thunder_entity_list_total', ['list' => $list]));
  }

}
