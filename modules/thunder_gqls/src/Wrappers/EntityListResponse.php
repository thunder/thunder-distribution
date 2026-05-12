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
class EntityListResponse implements EntityListResponseInterface, EntityListResponseHasNextInterface, ContainerInjectionInterface {

  /**
   * The query interface.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected QueryInterface $query;

  /**
   * The pagination offset.
   *
   * @var int
   */
  protected int $offset = 0;

  /**
   * The pagination limit.
   *
   * @var int
   */
  protected int $limit = 0;

  /**
   * EntityListResponse constructor.
   *
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $buffer
   *   The buffer parameter.
   */
  public function __construct(protected readonly EntityBuffer $buffer) {
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
   * Set offset.
   *
   * @param int $offset
   *   The offset.
   */
  public function setOffset(int $offset): EntityListResponse {
    $this->offset = $offset;
    return $this;
  }

  /**
   * Set limit.
   *
   * @param int $limit
   *   The limit.
   */
  public function setLimit(int $limit): EntityListResponse {
    $this->limit = $limit;
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
  public function items(): array|Deferred {
    $result = $this->query->execute();
    if (empty($result)) {
      return [];
    }

    $callback = $this->buffer->add($this->query->getEntityTypeId(), array_values($result));
    return new Deferred(fn() => $callback());
  }

  /**
   * Check if there are more items beyond the current page.
   *
   * @return bool
   *   TRUE if more items exist past the current offset + limit.
   */
  public function hasNext(): bool {
    return ($this->offset + $this->limit) < $this->total();
  }

}
