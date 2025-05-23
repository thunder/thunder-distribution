<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;

/**
 * The menu schema extension.
 *
 * @SchemaExtension(
 *   id = "thunder_bc_layer_basic_page_body",
 *   name = "Backwards compatibility layer for the basic page body field.",
 *   description = "The body field is available as body instead of content.",
 *   schema = "thunder"
 * )
 */
class ThunderBcLayerBasicPageBodySchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    parent::registerResolvers($registry);

    $this->registry->addFieldResolver('BasicPage', 'body',
      $this->builder->fromPath('entity', 'body.processed')
    );
  }

}
