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
  private QueryInterface $query;

  /**
   * The entity buffer.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityBuffer
   */
  private EntityBuffer $buffer;

  /**
   * EntityListResponse constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface|\Drupal\graphql\GraphQL\Buffers\EntityBuffer $parameter
   */
  public function __construct(QueryInterface|EntityBuffer $parameter) {
    if ($parameter instanceof QueryInterface) {
      @trigger_error('Calling the constructor with the query parameter is deprecated. Use service injection instead of directly instantiating and then use ::setQuery() instead.', E_USER_DEPRECATED);
      $this->setQuery($parameter);
      return;
    }
    $this->buffer = $parameter;
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

    if (empty($this->buffer)) {
      $this->buffer = \Drupal::service('graphql.buffer.entity');
    }

    $callback = $this->buffer->add($this->query->getEntityTypeId(), array_values($result));
    return new Deferred(fn() => $callback());
  }

}
