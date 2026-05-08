<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\thunder_gqls\Wrappers\EntityListResponseHasNextInterface;
use Drupal\thunder_gqls\Wrappers\EntityListResponseInterface;

/**
 * Checks if there are more items beyond the current page.
 */
#[DataProducer(
  id: 'thunder_entity_list_has_next',
  name: new TranslatableMarkup('Entity list has next'),
  description: new TranslatableMarkup('Whether there are more items beyond the current page.'),
  produces: new ContextDefinition('boolean', label: new TranslatableMarkup('Has next')),
  consumes: [
    'entityList' => new ContextDefinition('any', label: new TranslatableMarkup('Entity list response')),
  ]
)]
class ThunderEntityListHasNext extends DataProducerPluginBase {

  /**
   * Resolve has next.
   *
   * @param \Drupal\thunder_gqls\Wrappers\EntityListResponseInterface $entityList
   *   The entity list response.
   *
   * @return bool
   *   TRUE if more items exist past the current offset + limit.
   */
  public function resolve(EntityListResponseInterface $entityList): bool {
    assert($entityList instanceof EntityListResponseHasNextInterface);
    return $entityList->hasNext();
  }

}
