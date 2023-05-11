<?php

namespace Drupal\Tests\uuid_router\Unit\PathProcessor;

use Drupal\Tests\UnitTestCase;
use Drupal\uuid_router\PathProcessor\EntityUuidPathProcessor;

/**
 * @coversDefaultClass \Drupal\uuid_router\PathProcessor\EntityUuidPathProcessor
 * @group uuid_router
 */
class EntityUuidPathProcessorTest extends UnitTestCase{
  /**
   * The tested path processor.
   *
   * @var \Drupal\path_alias\PathProcessor\AliasPathProcessor
   */
  protected $pathProcessor;

  /**
   * Entity mocked type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->pathProcessor = new EntityUuidPathProcessor($this->entityTypeManager);
    $this->entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->storage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $this->entity = $this->createMock('Drupal\Core\Entity\EntityTypeInterface');
  }

  /**
   * Tests the processInbound method.
   *
   * @see \Drupal\path_alias\PathProcessor\AliasPathProcessor::processInbound
   */
  public function testProcessInbound() {
    $entityTypeId = 'node';
    $unKnownEntityTypeId = 'unknown';

    $uuid = 'uuid-that-exists';
    $unKnownUuid = 'uuid-that-not-exists';

    $relationShip

    $this->storage->expects($this->any())
      ->method('loadByProperties')
      ->willReturnMap([
        [['uuid' => $uuid], TRUE],
        [['uuid' => $unKnownUuid], FALSE],
      ]);

    $this->entityTypeManager->expects($this->any())
      ->method('hasDefinition')
      ->willReturnMap([
        [$entityTypeId, TRUE],
        [$unKnownEntityTypeId, FALSE],
      ]);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturnMap([
        [$entityTypeId, $this->storage],
      ]);

    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->willReturnMap([
        [$entityTypeId, $this->entity],
      ]);

    $this->entity->expects($this->any())
      ->method('hasLinkTemplate')
      ->willReturnMap([
        [$entityTypeId, $this->entity],
      ]);

    $request = Request::create('/urlalias');
    $this->assertEquals('internal-url', $this->pathProcessor->processInbound('urlalias', $request));
    $request = Request::create('/url');
    $this->assertEquals('url', $this->pathProcessor->processInbound('url', $request));
  }


}
