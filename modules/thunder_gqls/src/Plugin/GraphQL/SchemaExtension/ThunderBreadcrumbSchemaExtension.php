<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;

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
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    parent::registerResolvers($registry);

    $this->addFieldResolverIfNotExists('Query', 'breadcrumb',
      $this->builder->produce('thunder_breadcrumb')
        ->map('path', $this->builder->fromArgument('path')
      )
    );

  }

}
