<?php

namespace Drupal\thunder_gqls\GraphQL;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\thunder_gqls\Traits\ResolverHelperTrait;

/**
 * Type resolver for Paragraph interface.
 */
class ParagraphsTypeResolver extends DecoratableTypeResolver {

  use ResolverHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected function resolve(mixed $object) : ?string {
    if ($object instanceof ParagraphInterface) {
      return 'Paragraph' . $this->mapBundleToSchemaName($object->bundle());
    }
    return NULL;
  }

}
