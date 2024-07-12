<?php

namespace Drupal\thunder_gqls\GraphQL;

use Drupal\media\MediaInterface;
use Drupal\thunder_gqls\Traits\ResolverHelperTrait;

/**
 * Type resolver for Media interface.
 */
class MediaTypeResolver extends DecoratableTypeResolver {

  use ResolverHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected function resolve(mixed $object) : ?string {
    if ($object instanceof MediaInterface) {
      return 'Media' . $this->mapBundleToSchemaName($object->bundle());
    }
    return NULL;
  }

}
