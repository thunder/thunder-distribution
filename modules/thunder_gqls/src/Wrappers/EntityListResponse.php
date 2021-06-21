<?php

namespace Drupal\thunder_gqls\Wrappers;

use Drupal\Core\Entity\Query\QueryInterface;
use GraphQL\Deferred;

/**
 * The thunder entity list response class.
 */
class EntityListResponse {

  /**
   * The query interface.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * EntityListResponse constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query interface.
   */
  public function __construct(QueryInterface $query) {
    $this->query = $query;
  }

  /**
   * Calculate the total amount of results.
   *
   * @return int
   *   The total amount of results.
   */
  public function total() {
    $query = clone $this->query;
    $query->range(NULL, NULL)->count();
    return $query->execute();
  }

  /**
   * Retrieve the entity list.
   *
   * @return array|\GraphQL\Deferred
   *   The entity list.
   */
  public function items() {
    $result = $this->query->execute();
    if (empty($result)) {
      return [];
    }

    $buffer = \Drupal::service('graphql.buffer.entity');
    $callback = $buffer->add($this->query->getEntityTypeId(), array_values($result));
    return new Deferred(function () use ($callback) {
      return $callback();
    });
  }

}
