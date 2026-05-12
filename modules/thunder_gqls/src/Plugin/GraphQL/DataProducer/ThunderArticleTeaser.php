<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;

/**
 * Builds the teaser array from a Thunder article entity.
 */
#[DataProducer(
  id: "thunder_article_teaser",
  name: new TranslatableMarkup("Thunder Article Teaser"),
  description: new TranslatableMarkup("Builds the teaser array from a Thunder article entity."),
  produces: new ContextDefinition(
    data_type: "any",
    label: new TranslatableMarkup("Teaser")
  ),
  consumes: [
    "entity" => new ContextDefinition(
      data_type: "entity",
      label: new TranslatableMarkup("Entity")
    ),
  ]
)]
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
    $media = $entity->get('field_teaser_media')->entity;
    return [
      'image' => $media && $media->isPublished() ? $media : NULL,
      'text' => $entity->get('field_teaser_text')->value,
    ];
  }

}
