<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use GraphQL\Language\Source;

/**
 * The menu schema extension.
 *
 * @SchemaExtension(
 *   id = "thunder_breadcrumb",
 *   name = "Breadcrumb",
 *   description = "Adds the breadcrumb.",
 *   schema = "thunder"
 * )
 */
class ThunderBreadcrumbSchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getBaseDefinition(): ?Source {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    parent::registerResolvers($registry);

    $this->addFieldResolverIfNotExists('Query', 'breadcrumb',
      $this->builder->compose(
        $this->builder->produce('route_load')->map('path', $this->builder->fromArgument('path')),
        $this->builder->produce('thunder_breadcrumb')->map('url', $this->builder->fromParent())
      )
    );
  }

}
