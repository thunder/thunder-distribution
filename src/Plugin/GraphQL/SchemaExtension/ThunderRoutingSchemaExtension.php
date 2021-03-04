<?php

namespace Drupal\thunder_schema\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\media\MediaInterface;
use Drupal\paragraphs\ParagraphInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @SchemaExtension(
 *   id = "thunder_routing",
 *   name = "Routing extension",
 *   description = "Adds routing of URLs.",
 *   schema = "thunder"
 * )
 */
class ThunderRoutingSchemaExtension extends ThunderSchemaExtensionPluginBase {

  public function registerResolvers(ResolverRegistryInterface $registry) {
    parent::registerResolvers($registry);

    $this->registry->addFieldResolver('Query', 'route', $this->builder->compose(
      $this->builder->produce('route_load')
        ->map('path', $this->builder->fromArgument('path')),
      $this->builder->produce('route_entity')
        ->map('url', $this->builder->fromParent())

    ));
  }

}
