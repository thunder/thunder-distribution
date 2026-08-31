<?php

namespace Drupal\thunder_media;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Writes AI-disclosure metadata into image files using exiftool.
 */
class AiDisclosureWriter implements AiDisclosureWriterInterface {

  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * The exiftool binary path, FALSE if not found, or NULL if not resolved.
   */
  protected string|false|null $exiftoolPath = NULL;

  public function __construct(TranslationInterface $stringTranslation) {
    $this->stringTranslation = $stringTranslation;
  }

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
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    return $this->findExiftool() !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirements(): array {
    if ($this->isAvailable()) {
      return [];
    }
    return [
      'thunder_media_exiftool' => [
        'title' => $this->t('Thunder Media: exiftool'),
        'value' => $this->t('Not found'),
        'description' => $this->t('The "exiftool" binary was not found on PATH. Images flagged via the "AI disclosure" field will not have AI-disclosure metadata embedded until it is installed.'),
        'severity' => RequirementSeverity::Warning,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function readDigitalSourceType(string $realPath): ?string {
    $exiftool = $this->findExiftool();
    if ($exiftool === FALSE) {
      return NULL;
    }

    try {
      $process = new Process([
        $exiftool,
        '-s3',
        '-XMP-iptcExt:DigitalSourceType',
        $realPath,
      ]);
      $process->mustRun();
    }
    catch (ExceptionInterface $e) {
      $this->getLogger('thunder_media')->warning('Failed to read AI-disclosure metadata for @path: @message', [
        '@path' => $realPath,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }

    $value = trim($process->getOutput());
    if (!str_starts_with($value, self::BASE_URI)) {
      return NULL;
    }

    return substr($value, strlen(self::BASE_URI));
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

    try {
      $process = new Process([
        $exiftool,
        '-overwrite_original',
        '-XMP-iptcExt:DigitalSourceType=' . $value,
        $realPath,
      ]);
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
