<?php

namespace Drupal\paragraphs_paste\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Derives paragraph paste plugins handling OEmbed urls.
 */
class OEmbedUrlDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [
      'video' => [
        'id' => 'oembed_url:video',
        'label' => t('Remote video'),
        'description' => t('Youtube or Vimeo URLs.'),
        'providers' => ['YouTube', 'Vimeo'],
        'media_bundle' => 'video',
        'media_bundle_field' => 'field_media_video_embed_field',
        'paragraph_bundle' => 'video',
        'paragraph_bundle_field' => 'field_video',
      ],
      'twitter' => [
        'id' => 'oembed_url:twitter',
        'label' => t('Twitter'),
        'description' => t('Twitter URLs.'),
        'providers' => ['Twitter'],
        'media_bundle' => 'twitter',
        'media_bundle_field' => 'field_url',
        'paragraph_bundle' => 'twitter',
        'paragraph_bundle_field' => 'field_media',
      ],
      'instagram' => [
        'id' => 'oembed_url:instagram',
        'label' => t('Instagram'),
        'description' => t('Instagram URLs.'),
        'providers' => ['Instagram'],
        'media_bundle' => 'instagram',
        'media_bundle_field' => 'field_url',
        'paragraph_bundle' => 'instagram',
        'paragraph_bundle_field' => 'field_media',
      ],
    ];

    foreach ($this->derivatives as $name => $plugin_definition) {
      $this->derivatives[$name] = $plugin_definition + $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
