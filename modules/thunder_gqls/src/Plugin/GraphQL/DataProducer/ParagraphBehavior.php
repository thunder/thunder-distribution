<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Resolves the paragraphs options.
 *
 * @DataProducer(
 *   id = "paragraph_behavior",
 *   name = @Translation("Paragraph Behavior"),
 *   description = @Translation("Resolves the paragraph behavior."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Option")
 *   ),
 *   consumes = {
 *     "paragraph" = @ContextDefinition("entity:paragraph",
 *       label = @Translation("Root value")
 *     ),
 *     "behavior_plugin_id" = @ContextDefinition("string",
 *       label = @Translation("Paragraphs behavior plugin ID")
 *     ),
 *     "behavior_plugin_key" = @ContextDefinition("string",
 *       label = @Translation("Paragraphs behavior plugin key")
 *     ),
 *     "behavior_plugin_default" = @ContextDefinition("any",
 *       label = @Translation("Paragraphs behavior plugin default for this key"),
 *       required = FALSE,
 *       default_value = NULL,
 *     ),
 *     "throw_on_missing_plugin" = @ContextDefinition("boolean",
 *       label = @Translation("Whether to throw if behaviour plugin is not enabled"),
 *       required = FALSE,
 *       default_value = TRUE,
 *     )
 *   }
 * )
 */
class ParagraphBehavior extends DataProducerPluginBase {

  /**
   * Resolves the paragraph behavior.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   * @param string $behavior_plugin_id
   *   Plugin ID of paragraph behavior plugin.
   * @param string $behavior_plugin_key
   *   Key for requested value of this paragraph behavior plugin.
   * @param mixed $behavior_plugin_default
   *   Provided default value for requested key or NULL.
   * @param bool $throw_on_missing_plugin
   *   Whether to throw if behaviour plugin is not enabled.
   *
   * @return mixed
   *   Value of this paragraph behavior plugin key.
   *
   * @throws \Exception
   */
  public function resolve(ParagraphInterface $paragraph, string $behavior_plugin_id, string $behavior_plugin_key, mixed $behavior_plugin_default = NULL, bool $throw_on_missing_plugin = TRUE): mixed {
    if ($paragraph->getParagraphType()->hasEnabledBehaviorPlugin($behavior_plugin_id)) {
      return $paragraph->getBehaviorSetting($behavior_plugin_id, $behavior_plugin_key, $behavior_plugin_default);
    }

    if ($throw_on_missing_plugin) {
      throw new \Exception('Not enabled or invalid paragraphs behavior plugin.');
    }

    return $behavior_plugin_default;
  }

}
