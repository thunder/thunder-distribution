<?php

namespace Drupal\thunder_gqls\Wrappers;

/**
 * The thunder entity list response class.
 */
interface SearchApiResponseInterface extends EntityListResponseInterface {

  /**
   * Get search facets.
   *
   * @return array
   *   The facets.
   */
  public function facets(): array;

}
