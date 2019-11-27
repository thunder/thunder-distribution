<?php

namespace Drupal\paragraphs_paste\Plugin\ParagraphsPastePlugin;

use Drupal\paragraphs_paste\ParagraphsPastePluginBase;

/**
 * Defines the "oembed_url" plugin.
 *
 * @ParagraphsPastePlugin(
 *   id = "oembed_url",
 *   label = @Translation("OEmbed Urls"),
 *   module = "paragraphs_paste",
 *   weight = 0,
 *   providers = {},
 *   media_bundle = "",
 *   media_bundle_field = "",
 *   paragraph_bundle = "",
 *   paragraph_bundle_field = "",
 *   deriver = "\Drupal\paragraphs_paste\Plugin\Derivative\OEmbedUrlDeriver"
 * )
 */
class OEmbedUrl extends ParagraphsPastePluginBase {

  /**
   * {@inheritdoc}
   */
  public function build($input) {
    // Create media entity.
    $media_type = $this->entityTypeManager->getDefinition('media');
    $media_entity = $this->entityTypeManager->getStorage('media')
      ->create([
        $media_type->getKey('bundle') => $this->pluginDefinition['media_bundle'],
      ]);

    $media_entity->set($this->pluginDefinition['media_bundle_field'], $input);
    $media_entity->save();

    // Create paragraph entity and reference media entity.
    $entity_type = $this->entityTypeManager->getDefinition('paragraph');

    $paragraph_entity = $this->entityTypeManager->getStorage('paragraph')
      ->create([
        $entity_type->getKey('bundle') => $this->pluginDefinition['paragraph_bundle'],
      ]);

    $paragraph_entity->set($this->pluginDefinition['paragraph_bundle_field'], $media_entity);

    return $paragraph_entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($input, array $definition) {
    /** @var \Drupal\media\OEmbed\UrlResolverInterface $resolver */
    $resolver = \Drupal::service('media.oembed.url_resolver');

    foreach ($definition['providers'] as $provider_name) {
      try {
        $provider = $resolver->getProviderByUrl($input);
        if ($provider_name == $provider->getName()) {
          return TRUE;
        }
      }
      catch (\Exception $e) {
        continue;
      }
    }

    return FALSE;
  }

}
