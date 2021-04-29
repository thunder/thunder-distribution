<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;

/**
 * Extension to add the meta tags query.
 *
 * @SchemaExtension(
 *   id = "thunder_metatags",
 *   name = "Meta tags extension",
 *   description = "Adds the meta tags query.",
 *   schema = "thunder"
 * )
 */
class ThunderMetaTagsSchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    parent::registerResolvers($registry);

    $this->addFieldResolverIfNotExists('Query', 'metatags', $this->builder->compose(
      $this->builder->produce('route_load')
        ->map('path', $this->builder->fromArgument('path')),
      $this->builder->produce('route_entity')
        ->map('url', $this->builder->fromParent()),
      $this->builder->produce('thunder_metatags')
        ->map('type', $this->builder->fromValue('entity'))
        ->map('value', $this->builder->fromParent())
    ));

  }

}
