<?php

namespace Drupal\Tests\thunder_media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\thunder\Traits\ThunderKernelTestTrait;
use Drupal\thunder_media\AiDisclosureWriterInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests that saving an image media entity writes AI-disclosure metadata.
 *
 * @group Thunder
 */
class AiDisclosureWriterTest extends KernelTestBase {

  use ThunderKernelTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'media',
    'media_library',
    'views',
    'image',
    'file',
    'focal_point',
    'crop',
    'media_expire',
    'options',
    'thunder_media',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installConfig('system');
    $this->installEntitySchema('user');
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installSchema('file', 'file_usage');
    $this->installEntitySchema('crop');

    $this->container->get('config.installer')->installDefaultConfig('module', 'focal_point');
    $this->installThunderOptionalConfig();
  }

  /**
   * Overrides the AI-disclosure writer service with a mock.
   */
  protected function mockWriter(): MockObject {
    $mock = $this->createMock(AiDisclosureWriterInterface::class);
    $this->container->set('thunder_media.ai_disclosure_writer', $mock);
    return $mock;
  }

  /**
   * No disclosure is set: the writer must never be called.
   */
  public function testNoDisclosureIsNoOp(): void {
    $writer = $this->mockWriter();
    $writer->expects($this->never())->method('writeDigitalSourceType');

    $this->createSampleImageMedia();
  }

  /**
   * Selecting "Created with AI" writes the trainedAlgorithmicMedia term.
   */
  public function testCreatedWithAiIsWritten(): void {
    $writer = $this->mockWriter();
    $writer->expects($this->once())
      ->method('writeDigitalSourceType')
      ->with($this->isType('string'), 'trainedAlgorithmicMedia')
      ->willReturn(TRUE);

    $media = $this->createSampleImageMedia();
    $media->set('field_digital_source_type', 'trainedAlgorithmicMedia');
    $media->save();
  }

  /**
   * Selecting "Edited with AI" writes the composite AI term.
   */
  public function testEditedWithAiIsWritten(): void {
    $writer = $this->mockWriter();
    $writer->expects($this->once())
      ->method('writeDigitalSourceType')
      ->with($this->isType('string'), 'compositeWithTrainedAlgorithmicMedia')
      ->willReturn(TRUE);

    $media = $this->createSampleImageMedia();
    $media->set('field_digital_source_type', 'compositeWithTrainedAlgorithmicMedia');
    $media->save();
  }

  /**
   * Resaving without changing the field must not re-run the writer.
   */
  public function testUnchangedResaveDoesNotRewrite(): void {
    $writer = $this->mockWriter();
    $writer->expects($this->once())
      ->method('writeDigitalSourceType')
      ->willReturn(TRUE);

    $media = $this->createSampleImageMedia();
    $media->set('field_digital_source_type', 'trainedAlgorithmicMedia');
    $media->save();

    // Unrelated re-save: the field and the image did not change.
    $media->setName('Renamed');
    $media->save();
  }

}
