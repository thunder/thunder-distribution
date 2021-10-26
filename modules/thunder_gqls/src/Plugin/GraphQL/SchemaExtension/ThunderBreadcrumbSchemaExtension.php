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
  public function registerResolvers(ResolverRegistryInterface $registry) {
    parent::registerResolvers($registry);

    $this->addFieldResolverIfNotExists('Query', 'breadcrumb',
      $this->builder->produce('thunder_entity_sub_request')
        ->map('path', $this->builder->fromArgument('path'))
        ->map('key', $this->builder->fromValue('breadcrumb')
      )
    );

  }

}
