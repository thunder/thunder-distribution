<?php

namespace Drupal\thunder_media;

/**
 * Writes AI-disclosure metadata into image files.
 */
interface AiDisclosureWriterInterface {

  /**
   * Base URI of the IPTC "Digital Source Type" controlled vocabulary.
   */
  const BASE_URI = 'https://cv.iptc.org/newscodes/digitalsourcetype/';

  /**
   * Writes a Digital Source Type term into the image at the given path.
   *
   * @param string $realPath
   *   Local filesystem path to the image file.
   * @param string $term
   *   The IPTC Digital Source Type vocabulary term, e.g.
   *   'trainedAlgorithmicMedia'.
   *
   * @return bool
   *   TRUE on success, FALSE if the write could not be performed.
   */
  public function writeDigitalSourceType(string $realPath, string $term): bool;

}
