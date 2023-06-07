<?php

namespace Drupal\thunder_gqls\GraphQL;

use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\thunder_gqls\Traits\ResolverHelperTrait;
use Drupal\user\UserInterface;

/**
 * Type resolver for Paragraph interface.
 */
class ParagraphsTypeResolver extends DecoratableTypeResolver {

  use ResolverHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected function resolve($object) : ?string {
    if ($object instanceof ParagraphInterface) {
      return 'Paragraph' . $this->mapBundleToSchemaName($object->bundle());
    }
  }

}
