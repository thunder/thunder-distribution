<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Component\Serialization\Json;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Resolves the paragraphs options.
 *
 * @DataProducer(
 *   id = "paragraph_options",
 *   name = @Translation("Paragraphs Options"),
 *   description = @Translation("Resolves the paragraphs options."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Option")
 *   ),
 *   consumes = {
 *     "paragraph" = @ContextDefinition("entity",
 *       label = @Translation("Root value")
 *     )
 *   }
 * )
 */
class ParagraphOptions extends DataProducerPluginBase {

  /**
   * Resolves the paragraphs options.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The entity.
   *
   * @return mixed
   *   Returns the paragraphs options.
   */
  public function resolve(Paragraph $paragraph) {
    return Json::encode($paragraph->getAllBehaviorSettings());
  }

}
