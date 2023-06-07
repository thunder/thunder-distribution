<?php

namespace Drupal\thunder_gqls\GraphQL;

use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\thunder_gqls\Traits\ResolverHelperTrait;
use Drupal\user\UserInterface;

/**
 * Type resolver for Media interface.
 */
class MediaTypeResolver extends DecoratableTypeResolver {

  use ResolverHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected function resolve($object) : ?string {
    if ($object instanceof MediaInterface) {
      return 'Media' . $this->mapBundleToSchemaName($object->bundle());
    }
  }

}
