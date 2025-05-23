<?php

namespace Drupal\thunder_gqls\Wrappers;

use GraphQL\Deferred;

/**
 * The thunder entity list response class.
 */
interface EntityListResponseInterface {

  /**
   * Calculate the total amount of results.
   *
   * @return int
   *   The total amount of results.
   */
  public function total(): int;

  /**
   * Retrieve the entity list.
   *
   * @return array|\GraphQL\Deferred
   *   The entity list.
   */
  public function items(): array|Deferred;

}
