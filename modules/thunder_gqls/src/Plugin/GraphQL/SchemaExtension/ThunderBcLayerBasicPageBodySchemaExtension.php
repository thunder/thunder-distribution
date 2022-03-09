<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;

/**
 * The menu schema extension.
 *
 * @SchemaExtension(
 *   id = "thunder_bc_layer_basic_page_body",
 *   name = "BC layer for basic page body field.",
 *   description = "BC layer for basic page body field. Not needed, if you use the paragraphs field instead of the body field in the basic page content type.",
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
