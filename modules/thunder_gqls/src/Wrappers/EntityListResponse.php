<?php

namespace Drupal\thunder_gqls\Wrappers;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The thunder entity list response class.
 */
class EntityListResponse implements EntityListResponseInterface, ContainerInjectionInterface {

  /**
   * The query interface.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected QueryInterface $query;

  /**
   * The entity buffer.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityBuffer
   */
  protected EntityBuffer $buffer;

  /**
   * EntityListResponse constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface|\Drupal\graphql\GraphQL\Buffers\EntityBuffer $buffer
   *   The query or buffer parameter.
   */
  public function __construct(QueryInterface|EntityBuffer $buffer) {
    if ($buffer instanceof QueryInterface) {
      // phpcs:ignore
      @trigger_error('Calling the constructor with a query parameter is deprecated in Thunder 7.3.3 and will be removed in Thunder 8.0. Use service injection and ::setQuery() instead.', E_USER_DEPRECATED);
      $this->setQuery($buffer);
      // phpcs:ignore
      $buffer = \Drupal::service('graphql.buffer.entity');
    }
    $this->buffer = $buffer;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('graphql.buffer.entity'),
    );
  }

  /**
   * Set query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query.
   */
  public function setQuery(QueryInterface $query): EntityListResponse {
    $this->query = $query;
    return $this;
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
  public function items() {
    $result = $this->query->execute();
    if (empty($result)) {
      return [];
    }

    $callback = $this->buffer->add($this->query->getEntityTypeId(), array_values($result));
    return new Deferred(fn() => $callback());
  }

}
