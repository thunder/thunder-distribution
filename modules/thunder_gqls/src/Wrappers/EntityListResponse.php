<?php

namespace Drupal\thunder_gqls\Wrappers;

use Drupal\Core\Entity\Query\QueryInterface;
use GraphQL\Deferred;

/**
 * The thunder entity list response class.
 */
readonly class EntityListResponse implements EntityListResponseInterface {

  /**
   * EntityListResponse constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query interface.
   */
  public function __construct(protected QueryInterface $query) {
  }

  /**
   * Calculate the total amount of results.
   *
   * @return int
   *   The total amount of results.
   */
  public function total(): int {
    $query = clone $this->query;
    $query->range(NULL, NULL)->count();
    return (int) $query->execute();
  }

  /**
   * Retrieve the entity list.
   *
   * @return array|\GraphQL\Deferred
   *   The entity list.
   */
  public function items(): array|Deferred {
    $result = $this->query->execute();
    if (empty($result)) {
      return [];
    }

    $buffer = \Drupal::service('graphql.buffer.entity');
    $callback = $buffer->add($this->query->getEntityTypeId(), array_values($result));
    return new Deferred(fn() => $callback());
  }

}
