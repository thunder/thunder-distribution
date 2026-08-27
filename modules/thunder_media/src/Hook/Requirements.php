<?php

namespace Drupal\thunder_media\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\thunder_media\AiDisclosureWriterInterface;

/**
 * Runtime requirements hooks for the thunder_media module.
 */
class Requirements {

  public function __construct(
    protected readonly AiDisclosureWriterInterface $writer,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtimeRequirements(): array {
    return $this->writer->getRequirements();
  }

}
