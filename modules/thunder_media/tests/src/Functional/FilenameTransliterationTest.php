<?php

namespace Drupal\Tests\thunder_media\Functional;

use Drupal\Core\File\FileExists;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Tests\thunder\Functional\ThunderTestBase;
use Drupal\file\Entity\File;

/**
 * Tests for transliteration of file names.
 *
 * @group Thunder
 */
class FilenameTransliterationTest extends ThunderTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test', 'file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {

    parent::setUp();

    $this->config('file.settings')
      ->set('filename_sanitization.transliterate', TRUE)
      ->set('filename_sanitization.replace_whitespace', TRUE)
      ->set('filename_sanitization.replace_non_alphanumeric', TRUE)
      ->set('filename_sanitization.deduplicate_separators', TRUE)
      ->save();
  }

  /**
   * Test for transliteration of file name.
   */
  public function testFileTransliteration(): void {

    $account = $this->drupalCreateUser(['access site reports']);
    $this->drupalLogin($account);

    if (file_exists('core/tests/fixtures/files/image-1.png')) {
      \Drupal::service('file_system')->copy('core/tests/fixtures/files/image-1.png', PublicStream::basePath() . '/foo°.png');
    }
    else {
      // Needed for min testing.
      /** @var \Drupal\Core\Extension\ExtensionPathResolver $extensionPathResolver */
      $extensionPathResolver = \Drupal::service('extension.path.resolver');
      $original = $extensionPathResolver->getPath('module', 'simpletest') . '/files';
      \Drupal::service('file_system')->copy($original . '/image-1.png', PublicStream::basePath() . '/foo°.png');
    }

    // Upload with replace to guarantee there's something there.
    $edit = [
      'file_test_replace' => FileExists::Rename->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath('public://foo°.png'),
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('You WIN!');

    $this->assertTrue(file_exists('temporary://foodeg.png'));

    $max_fid_after = \Drupal::database()->query('SELECT MAX(fid) AS fid FROM {file_managed}')->fetchField();

    $file = File::load($max_fid_after);

    $this->assertSame('foodeg.png', $file->getFilename());
    $this->assertSame('temporary://foodeg.png', $file->getFileUri());

  }

}
