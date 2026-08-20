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
    $exiftool = $this->findExiftool();
    if ($exiftool === FALSE) {
      $this->getLogger('thunder_media')->warning('Cannot write AI-disclosure metadata to @path: the "exiftool" binary was not found on PATH.', ['@path' => $realPath]);
      return FALSE;
    }

    $process = new Process([
      $exiftool,
      '-overwrite_original',
      '-XMP-iptcExt:DigitalSourceType=' . self::BASE_URI . $term,
      $realPath,
    ]);

    try {
      $process->mustRun();
    }
    catch (ExceptionInterface $e) {
      $this->getLogger('thunder_media')->warning('Failed to write AI-disclosure metadata to @path: @message', [
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
