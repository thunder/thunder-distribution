<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Builds the teaser array from a Thunder article entity.
 *
 * @DataProducer(
 *   id = "thunder_article_teaser",
 *   name = @Translation("Thunder Article Teaser"),
 *   description = @Translation("Builds the teaser array from a Thunder article entity."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Teaser")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class ThunderArticleTeaser extends DataProducerPluginBase {

  /**
   * Resolves the teaser fields from the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return array
   *   Array with 'image' and 'text' teaser fields.
   */
  public function resolve(ContentEntityInterface $entity): array {
    return [
      'image' => $entity->get('field_teaser_media')->entity,
      'text' => $entity->get('field_teaser_text')->value,
    ];
  }

}
