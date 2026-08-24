<?php

namespace Drupal\Tests\thunder_media\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\thunder\Traits\ThunderKernelTestTrait;
use Drupal\media\Entity\Media;
use Drupal\thunder_media\AiDisclosureWriterInterface;
use Drupal\thunder_media\Hook\MediaForm;

/**
 * Tests pre-selecting an embedded AI disclosure before the first save.
 *
 * @group Thunder
 */
class MediaFormTest extends KernelTestBase {

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
   * Builds a MediaForm hook instance backed by a mock writer.
   */
  protected function mediaForm(AiDisclosureWriterInterface $writer): MediaForm {
    return new MediaForm(
      $this->container->get('config.factory'),
      $writer,
      $this->container->get('file_system'),
    );
  }

  /**
   * An embedded disclosure is pre-selected on a new, unsaved media item.
   */
  public function testPrefillsFromEmbeddedDisclosure(): void {
    $image = $this->createSampleFile('image');
    $realPath = $this->container->get('file_system')->realpath($image->getFileUri());

    $writer = $this->createMock(AiDisclosureWriterInterface::class);
    $writer->expects($this->once())
      ->method('readDigitalSourceType')
      ->with($realPath)
      ->willReturn('trainedAlgorithmicMedia');

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_image' => ['target_id' => $image->id()],
    ]);

    $element = [];
    $this->mediaForm($writer)->fieldWidgetSingleElementOptionsSelectFormAlter(
      $element,
      new FormState(),
      ['items' => $media->get('field_digital_source_type')]
    );

    $this->assertSame('trainedAlgorithmicMedia', $element['#default_value']);
  }

  /**
   * An unknown embedded value is not pre-selected.
   */
  public function testDoesNotPrefillUnknownValue(): void {
    $image = $this->createSampleFile('image');

    $writer = $this->createMock(AiDisclosureWriterInterface::class);
    $writer->method('readDigitalSourceType')->willReturn('digitalCapture');

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_image' => ['target_id' => $image->id()],
    ]);

    $element = [];
    $this->mediaForm($writer)->fieldWidgetSingleElementOptionsSelectFormAlter(
      $element,
      new FormState(),
      ['items' => $media->get('field_digital_source_type')]
    );

    $this->assertArrayNotHasKey('#default_value', $element);
  }

  /**
   * Disabling auto-detect skips reading the file entirely.
   */
  public function testSkipsWhenAutoDetectDisabled(): void {
    $this->config('thunder_media.settings')->set('ai_disclosure_auto_detect', FALSE)->save();

    $image = $this->createSampleFile('image');
    $writer = $this->createMock(AiDisclosureWriterInterface::class);
    $writer->expects($this->never())->method('readDigitalSourceType');

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_image' => ['target_id' => $image->id()],
    ]);

    $element = [];
    $this->mediaForm($writer)->fieldWidgetSingleElementOptionsSelectFormAlter(
      $element,
      new FormState(),
      ['items' => $media->get('field_digital_source_type')]
    );

    $this->assertArrayNotHasKey('#default_value', $element);
  }

  /**
   * A field already set by the editor is not overridden.
   */
  public function testDoesNotOverrideAlreadySetValue(): void {
    $image = $this->createSampleFile('image');
    $writer = $this->createMock(AiDisclosureWriterInterface::class);
    $writer->expects($this->never())->method('readDigitalSourceType');

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_image' => ['target_id' => $image->id()],
      'field_digital_source_type' => 'compositeWithTrainedAlgorithmicMedia',
    ]);

    $element = [];
    $this->mediaForm($writer)->fieldWidgetSingleElementOptionsSelectFormAlter(
      $element,
      new FormState(),
      ['items' => $media->get('field_digital_source_type')]
    );

    $this->assertArrayNotHasKey('#default_value', $element);
  }

  /**
   * The file is only read once per form-build session, not on every rebuild.
   */
  public function testOnlyReadsOncePerFormState(): void {
    $image = $this->createSampleFile('image');

    $writer = $this->createMock(AiDisclosureWriterInterface::class);
    $writer->expects($this->once())
      ->method('readDigitalSourceType')
      ->willReturn('trainedAlgorithmicMedia');

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_image' => ['target_id' => $image->id()],
    ]);

    $form_state = new FormState();
    $media_form = $this->mediaForm($writer);

    $first = [];
    $media_form->fieldWidgetSingleElementOptionsSelectFormAlter($first, $form_state, ['items' => $media->get('field_digital_source_type')]);

    // Simulate an AJAX rebuild reusing the same form state.
    $second = [];
    $media_form->fieldWidgetSingleElementOptionsSelectFormAlter($second, $form_state, ['items' => $media->get('field_digital_source_type')]);

    $this->assertSame('trainedAlgorithmicMedia', $first['#default_value']);
    $this->assertArrayNotHasKey('#default_value', $second);
  }

}
