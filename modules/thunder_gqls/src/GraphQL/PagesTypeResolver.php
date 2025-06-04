<?php

namespace Drupal\thunder_gqls\GraphQL;

use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\thunder_gqls\Traits\ResolverHelperTrait;
use Drupal\user\UserInterface;

/**
 * Type resolver for Page interface.
 */
class PagesTypeResolver extends DecoratableTypeResolver {

  use ResolverHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected function resolve(mixed $object) : ?string {
    if ($object instanceof NodeInterface || $object instanceof TermInterface || $object instanceof UserInterface) {
      if ($object->bundle() === 'page') {
        return 'BasicPage';
      }
      return $this->mapBundleToSchemaName($object->bundle());
    }
    return NULL;
  }

}
