<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Resolves the paragraphs summary.
 *
 * @DataProducer(
 *   id = "paragraph_summary",
 *   name = @Translation("Paragraphs Summary"),
 *   description = @Translation("Resolves the paragraphs summary."),
 *   produces = @ContextDefinition("any",
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
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The entity.
   *
   * @return mixed
   *   The paragraphs summary.
   */
  public function resolve(Paragraph $paragraph) {
    return $paragraph->getSummaryItems()['content'];
  }

}
