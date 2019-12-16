<?php

namespace Drupal\paragraphs_paste\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Derives paragraph paste plugins handling OEmbed urls.
 */
class OEmbedUrlDeriver extends DeriverBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [
      'video' => [
        'id' => 'oembed_url:video',
        'label' => $this->t('Remote video'),
        'description' => $this->t('Youtube or Vimeo URLs.'),
        'providers' => ['YouTube', 'Vimeo'],
        'media_bundle' => 'video',
        'media_bundle_field' => 'field_media_video_embed_field',
        'paragraph_bundle' => 'video',
        'paragraph_bundle_field' => 'field_video',
      ],
      'twitter' => [
        'id' => 'oembed_url:twitter',
        'label' => $this->t('Twitter'),
        'description' => $this->t('Twitter URLs.'),
        'providers' => ['Twitter'],
        'media_bundle' => 'twitter',
        'media_bundle_field' => 'field_url',
        'paragraph_bundle' => 'twitter',
        'paragraph_bundle_field' => 'field_media',
      ],
      'instagram' => [
        'id' => 'oembed_url:instagram',
        'label' => $this->t('Instagram'),
        'description' => $this->t('Instagram URLs.'),
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
