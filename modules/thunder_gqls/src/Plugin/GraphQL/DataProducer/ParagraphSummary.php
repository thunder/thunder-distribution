<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Resolves the paragraphs summary.
 *
 * @DataProducer(
 *   id = "paragraph_summary",
 *   name = @Translation("Paragraphs Summary"),
 *   description = @Translation("Resolves the paragraphs summary."),
 *   produces = @ContextDefinition("map",
 *     label = @Translation("Summary")
 *   ),
 *   consumes = {
 *     "paragraph" = @ContextDefinition("entity",
 *       label = @Translation("Root value")
 *     )
 *   }
 * )
 */
class ParagraphSummary extends DataProducerPluginBase {

  /**
   * Resolves the paragraphs summary.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The entity.
   *
   * @return array
   *   The paragraphs summary.
   */
  public function resolve(ParagraphInterface $paragraph): array {
    return $paragraph->getSummaryItems()['content'] ?? [];
  }

}
