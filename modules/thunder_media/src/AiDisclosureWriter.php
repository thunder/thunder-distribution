<?php

namespace Drupal\thunder_media;

use Drupal\Core\Logger\LoggerChannelTrait;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Writes AI-disclosure metadata into image files using exiftool.
 */
class AiDisclosureWriter implements AiDisclosureWriterInterface {

  use LoggerChannelTrait;

  /**
   * The exiftool binary path, FALSE if not found, or NULL if not resolved.
   */
  protected string|false|null $exiftoolPath = NULL;

  /**
   * {@inheritdoc}
   */
  public function writeDigitalSourceType(string $realPath, string $term): bool {
    return $this->runExiftool($realPath, self::BASE_URI . $term, 'write');
  }

  /**
   * {@inheritdoc}
   */
  public function clearDigitalSourceType(string $realPath): bool {
    return $this->runExiftool($realPath, '', 'remove');
  }

  /**
   * Sets or clears the DigitalSourceType XMP tag on the given image.
   *
   * @param string $realPath
   *   Local filesystem path to the image file.
   * @param string $value
   *   The value to assign to the tag; an empty string deletes the tag.
   * @param string $action
   *   The action name used in log messages ('write' or 'remove').
   *
   * @return bool
   *   TRUE on success, FALSE if the operation could not be performed.
   */
  protected function runExiftool(string $realPath, string $value, string $action): bool {
    $exiftool = $this->findExiftool();
    if ($exiftool === FALSE) {
      $this->getLogger('thunder_media')->warning('Cannot @action AI-disclosure metadata for @path: the "exiftool" binary was not found on PATH.', [
        '@action' => $action,
        '@path' => $realPath,
      ]);
      return FALSE;
    }

    $process = new Process([
      $exiftool,
      '-overwrite_original',
      '-XMP-iptcExt:DigitalSourceType=' . $value,
      $realPath,
    ]);

    try {
      $process->mustRun();
    }
    catch (ExceptionInterface $e) {
      $this->getLogger('thunder_media')->warning('Failed to @action AI-disclosure metadata for @path: @message', [
        '@action' => $action,
        '@path' => $realPath,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Resolves the exiftool binary path on PATH, caching the result.
   *
   * @return string|false
   *   The resolved path, or FALSE if exiftool could not be found.
   */
  protected function findExiftool(): string|false {
    if ($this->exiftoolPath === NULL) {
      $this->exiftoolPath = (new ExecutableFinder())->find('exiftool') ?? FALSE;
    }
    return $this->exiftoolPath;
  }

}
