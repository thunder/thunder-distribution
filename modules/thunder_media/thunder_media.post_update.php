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

/**
 * Add the "AI disclosure" settings.
 */
function thunder_media_post_update_add_ai_disclosure_settings(): string {
  /** @var \Drupal\update_helper\Updater $updater */
  $updater = \Drupal::service('update_helper.updater');

  try {
    $updater->executeUpdate('thunder_media', 'thunder_media_post_update_add_ai_disclosure_settings');
  }
  catch (\Exception $e) {
    \Drupal::logger('thunder_media')->warning('Could not add the AI disclosure settings: @message', ['@message' => $e->getMessage()]);
  }

  return $updater->logger()->output();
}
