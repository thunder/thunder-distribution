<?php

namespace Drupal\Tests\thunder\Traits;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;

/**
 * Use this trait to create config and data in kernel tests.
 */
trait ThunderKernelTestTrait {

  use TestFileCreationTrait;

  /**
   * Creates a file.
   *
   * @param string $fileType
   *   The type of file to create.
   *
   * @return \Drupal\file\FileInterface
   *   The File entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createSampleFile(string $fileType): FileInterface {
    /** @var \stdClass $testFile */
    $testFile = $this->getTestFiles($fileType)[0];

    /** @var \Drupal\file\FileInterface $fileEntity */
    $fileEntity = File::create([
      'uri' => $testFile->uri,
    ]);
    $fileEntity->save();

    return $fileEntity;
  }

  /**
   * Create image media entity.
   *
   * @return \Drupal\media\MediaInterface
   *   The media entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createSampleImageMedia(): MediaInterface {
    $image = $this->createSampleFile('image');

    /** @var \Drupal\media\MediaInterface $mediaImage */
    $mediaImage = Media::create([
      'bundle' => 'image',
      'name' => 'Test image media',
      'field_image' => [
        'target_id' => $image->id(),
        'alt' => 'Alt text',
        'title' => 'Title text',
      ],
    ]);
    $mediaImage->save();

    return $mediaImage;
  }

  /**
   * Install thunder optional config.
   */
  protected function installThunderOptionalConfig(): void {
    /** @var \Drupal\Core\Config\ConfigInstallerInterface $configInstaller */
    $configInstaller = $this->container->get('config.installer');
    $extension_path = $this->container->get('extension.list.profile')
      ->getPath('thunder');
    $optional_install_path = $extension_path . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
    $storage = new FileStorage($optional_install_path);
    $configInstaller->installOptionalConfig($storage);
  }

}
