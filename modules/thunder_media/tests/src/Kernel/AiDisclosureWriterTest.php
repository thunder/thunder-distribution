<?php

namespace Drupal\Tests\thunder_media\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\thunder\Traits\ThunderKernelTestTrait;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
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

    $media = $this->createSampleImageMedia();
    $realPath = $this->realPathOfImage($media);

    $writer->expects($this->once())
      ->method('writeDigitalSourceType')
      ->with($realPath, 'trainedAlgorithmicMedia')
      ->willReturn(TRUE);

    $media->set('field_digital_source_type', 'trainedAlgorithmicMedia');
    $media->save();
  }

  /**
   * Selecting "Edited with AI" writes the composite AI term.
   */
  public function testEditedWithAiIsWritten(): void {
    $writer = $this->mockWriter();

    $media = $this->createSampleImageMedia();
    $realPath = $this->realPathOfImage($media);

    $writer->expects($this->once())
      ->method('writeDigitalSourceType')
      ->with($realPath, 'compositeWithTrainedAlgorithmicMedia')
      ->willReturn(TRUE);

    $media->set('field_digital_source_type', 'compositeWithTrainedAlgorithmicMedia');
    $media->save();
  }

  /**
   * Setting the disclosure on brand-new media (upload time) writes it too.
   *
   * There is no "original" entity to diff against on insert, so this
   * guards against the write being skipped as a no-op change.
   */
  public function testDisclosureSetAtCreationIsWritten(): void {
    $writer = $this->mockWriter();

    $image = $this->createSampleFile('image');
    $realPath = $this->container->get('file_system')->realpath($image->getFileUri());

    $writer->expects($this->once())
      ->method('writeDigitalSourceType')
      ->with($realPath, 'trainedAlgorithmicMedia')
      ->willReturn(TRUE);

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_image' => ['target_id' => $image->id()],
      'field_digital_source_type' => 'trainedAlgorithmicMedia',
    ]);
    $media->save();
  }

  /**
   * Resolves the real filesystem path of a media entity's image file.
   */
  protected function realPathOfImage(MediaInterface $media): string {
    $file = $media->get('field_image')->entity;
    assert($file instanceof FileInterface);
    return $this->container->get('file_system')->realpath($file->getFileUri());
  }

  /**
   * Switching the disclosure back to "N/A" clears the embedded metadata.
   */
  public function testClearingDisclosureRemovesMetadata(): void {
    $writer = $this->mockWriter();

    $media = $this->createSampleImageMedia();
    $realPath = $this->realPathOfImage($media);

    $writer->expects($this->once())
      ->method('writeDigitalSourceType')
      ->with($realPath, 'trainedAlgorithmicMedia')
      ->willReturn(TRUE);
    $writer->expects($this->once())
      ->method('clearDigitalSourceType')
      ->with($realPath)
      ->willReturn(TRUE);

    $media->set('field_digital_source_type', 'trainedAlgorithmicMedia');
    $media->save();

    $media->set('field_digital_source_type', '');
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
