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
   *
   * Must be called before any media entity is saved in the test.
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
   * A disclosure already embedded in an uploaded file is adopted.
   */
  public function testExistingDisclosureIsAdoptedFromUpload(): void {
    $writer = $this->mockWriter();

    $image = $this->createSampleFile('image');
    $realPath = $this->container->get('file_system')->realpath($image->getFileUri());

    $writer->method('readDigitalSourceType')->with($realPath)->willReturn('trainedAlgorithmicMedia');
    $writer->expects($this->once())
      ->method('writeDigitalSourceType')
      ->with($realPath, 'trainedAlgorithmicMedia')
      ->willReturn(TRUE);

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_image' => ['target_id' => $image->id()],
    ]);
    $media->save();

    $this->assertSame('trainedAlgorithmicMedia', $media->get('field_digital_source_type')->value);
  }

  /**
   * An embedded term outside the field's allowed values is ignored.
   */
  public function testUnknownEmbeddedDisclosureIsIgnored(): void {
    $writer = $this->mockWriter();

    $image = $this->createSampleFile('image');
    $writer->method('readDigitalSourceType')->willReturn('digitalCapture');
    $writer->expects($this->never())->method('writeDigitalSourceType');
    $writer->expects($this->never())->method('clearDigitalSourceType');

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_image' => ['target_id' => $image->id()],
    ]);
    $media->save();

    $this->assertSame('', $media->get('field_digital_source_type')->value ?? '');
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

  /**
   * The upload-only lock is enforced in mediaPresave() itself.
   *
   * A direct field write on a locked, already-uploaded item must not change.
   */
  public function testUploadOnlyLockIsEnforcedInPresave(): void {
    $writer = $this->mockWriter();
    // Creating the (new) sample media is unlocked and attempts adoption
    // once; the sample file carries no embedded disclosure.
    $writer->expects($this->once())->method('readDigitalSourceType')->willReturn(NULL);
    $writer->expects($this->never())->method('writeDigitalSourceType');
    $writer->expects($this->never())->method('clearDigitalSourceType');

    $media = $this->createSampleImageMedia();

    $this->config('thunder_media.settings')->set('ai_disclosure_upload_only', TRUE)->save();

    $newImage = $this->createSampleFile('image');
    $media->set('field_image', ['target_id' => $newImage->id()]);
    $media->set('field_digital_source_type', 'trainedAlgorithmicMedia');
    $media->save();

    $this->assertSame('', $media->get('field_digital_source_type')->value ?? '');
  }

  /**
   * Explicitly clearing the field is not overridden by adoption.
   */
  public function testExplicitClearIsNotOverriddenByAdoption(): void {
    $writer = $this->mockWriter();

    $media = $this->createSampleImageMedia();
    $realPath = $this->realPathOfImage($media);

    $writer->expects($this->once())
      ->method('writeDigitalSourceType')
      ->with($realPath, 'trainedAlgorithmicMedia')
      ->willReturn(TRUE);

    $media->set('field_digital_source_type', 'trainedAlgorithmicMedia');
    $media->save();

    $newImage = $this->createSampleFile('image');
    $newRealPath = $this->container->get('file_system')->realpath($newImage->getFileUri());

    // The new file carries an adoptable disclosure, but the explicit
    // clear must win: adoption must not even be attempted.
    $writer->expects($this->never())->method('readDigitalSourceType');
    $writer->expects($this->once())
      ->method('clearDigitalSourceType')
      ->with($newRealPath)
      ->willReturn(TRUE);

    $media->set('field_image', ['target_id' => $newImage->id()]);
    $media->set('field_digital_source_type', '');
    $media->save();

    $this->assertSame('', $media->get('field_digital_source_type')->value ?? '');
  }

  /**
   * A failed write reverts the field instead of leaving it out of sync.
   */
  public function testWriteFailureRevertsField(): void {
    $writer = $this->mockWriter();

    $media = $this->createSampleImageMedia();
    $realPath = $this->realPathOfImage($media);

    $writer->expects($this->once())
      ->method('writeDigitalSourceType')
      ->with($realPath, 'trainedAlgorithmicMedia')
      ->willReturn(FALSE);

    $media->set('field_digital_source_type', 'trainedAlgorithmicMedia');
    $media->save();

    $this->assertSame('', $media->get('field_digital_source_type')->value ?? '');
  }

  /**
   * Disabling auto-detect skips adopting a disclosure from an upload.
   */
  public function testAutoDetectDisabledSkipsAdoption(): void {
    $this->config('thunder_media.settings')->set('ai_disclosure_auto_detect', FALSE)->save();

    $writer = $this->mockWriter();
    $writer->expects($this->never())->method('readDigitalSourceType');
    $writer->expects($this->never())->method('writeDigitalSourceType');

    $image = $this->createSampleFile('image');

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_image' => ['target_id' => $image->id()],
    ]);
    $media->save();

    $this->assertSame('', $media->get('field_digital_source_type')->value ?? '');
  }

}
