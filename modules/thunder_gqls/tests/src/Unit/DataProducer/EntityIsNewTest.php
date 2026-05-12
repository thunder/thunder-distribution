<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_gqls\Unit\DataProducer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityIsNew;

/**
 * @coversDefaultClass \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityIsNew
 * @group Thunder
 */
class EntityIsNewTest extends UnitTestCase {

  /**
   * The data producer under test.
   *
   * @var \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\EntityIsNew
   */
  protected EntityIsNew $producer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->producer = new EntityIsNew([], 'entity_is_new', []);
  }

  /**
   * @covers ::resolve
   */
  public function testNewEntityReturnsTrue(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('isNew')->willReturn(TRUE);
    $this->assertTrue($this->producer->resolve($entity));
  }

  /**
   * @covers ::resolve
   */
  public function testSavedEntityReturnsFalse(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('isNew')->willReturn(FALSE);
    $this->assertFalse($this->producer->resolve($entity));
  }

}
