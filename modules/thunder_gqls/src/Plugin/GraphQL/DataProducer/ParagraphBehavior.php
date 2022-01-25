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
 *     "paragraph" = @ContextDefinition("entity",
 *       label = @Translation("Root value")
 *     ),
 *     "behavior_plugin_id" = @ContextDefinition("string",
 *       label = @Translation("Paragraphs behavior plugin ID")
 *     ),
 *     "behavior_plugin_key" = @ContextDefinition("string",
 *       label = @Translation("Paragraphs behavior plugin key")
 *     ),
 *   }
 * )
 */
class ParagraphBehavior extends DataProducerPluginBase {

  /**
   * Resolves the paragraph behavior.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The entity.
   * @param string $behavior_plugin_id
   * @param string $behavior_plugin_key
   *
   * @return mixed
   */
  public function resolve(ParagraphInterface $paragraph, string $behavior_plugin_id, string $behavior_plugin_key) {
    return $paragraph->getBehaviorSetting($behavior_plugin_id, $behavior_plugin_key);
  }

}
