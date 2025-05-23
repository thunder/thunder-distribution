<?php

/**
 * @file
 * Post update hooks for Thunder Media.
 */

/**
 * Use Drupal core's filename transliteration instead of Thunder's.
 */
function thunder_media_post_update_filename_transliteration(): void {
  $thunder_media_config = \Drupal::configFactory()->getEditable('thunder_media.settings');

  if ($thunder_media_config->get('enable_filename_transliteration')) {
    $file_config = \Drupal::configFactory()->getEditable('file.settings');
    $file_config
      ->set('filename_sanitization.transliterate', TRUE)
      ->set('filename_sanitization.replace_whitespace', TRUE)
      ->set('filename_sanitization.replace_non_alphanumeric', TRUE)
      ->set('filename_sanitization.deduplicate_separators', TRUE)
      ->save();
  }

  $thunder_media_config->clear('enable_filename_transliteration')->save();
}
