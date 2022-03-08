<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;

/**
 * The menu schema extension.
 *
 * @SchemaExtension(
 *   id = "thunder_bc_layer_basic_page_content",
 *   name = "BC layer for basic page content field.",
 *   description = "BC layer for basic page content field.",
 *   schema = "thunder"
 * )
 */
class ThunderBcLayerBasicPageContentSchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    parent::registerResolvers($registry);

    $this->registry->addFieldResolver('BasicPage', 'content',
      $this->builder->fromPath('entity', 'body.processed')
    );
  }

}
