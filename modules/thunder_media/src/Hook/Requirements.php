<?php

namespace Drupal\thunder_media\Hook;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\thunder_media\AiDisclosureWriterInterface;

/**
 * Runtime requirements hooks for the thunder_media module.
 */
class Requirements {

  use StringTranslationTrait;

  public function __construct(
    protected readonly AiDisclosureWriterInterface $writer,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtimeRequirements(): array {
    $requirements = [];

    if (!$this->writer->isAvailable()) {
      $requirements['thunder_media_exiftool'] = [
        'title' => $this->t('Thunder Media: exiftool'),
        'value' => $this->t('Not found'),
        'description' => $this->t('The "exiftool" binary was not found on PATH. Images flagged via the "AI disclosure" field will not have AI-disclosure metadata embedded until it is installed.'),
        'severity' => RequirementSeverity::Warning,
      ];
    }

    return $requirements;
  }

}
