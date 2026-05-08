<?php

namespace Drupal\thunder_gqls\Wrappers;

namespace Drupal\thunder_gqls\Wrappers;

/**
 * The thunder entity list response class.
 */
interface EntityListResponseHasNextInterface {

  /**
   * Whether there are more items beyond the current page.
   *
   * @return bool
   *   TRUE if more items exist past the current offset + limit.
   */
  public function hasNext(): bool;

}
