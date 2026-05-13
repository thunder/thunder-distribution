<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityIsNew
 * @group Thunder
 */
class EntityIsNewTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
  ];

  /**
   * @covers ::resolve
   */
  public function testNewEntityReturnsTrue(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('isNew')->willReturn(TRUE);
    $this->assertTrue($this->executeDataProducer('entity_is_new', ['entity' => $entity]));
  }

  /**
   * @covers ::resolve
   */
  public function testSavedEntityReturnsFalse(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('isNew')->willReturn(FALSE);
    $this->assertFalse($this->executeDataProducer('entity_is_new', ['entity' => $entity]));
  }

}
