<?php

namespace Drupal\thunder_xymatic\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension\ThunderSchemaExtensionPluginBase;

/**
 * The Thunder VG Wort schema extension.
 *
 * @SchemaExtension(
 *   id = "thunder_xymatic",
 *   name = "Thunder Xymatic extension",
 *   description = "Adds media xymatic fields.",
 *   schema = "thunder"
 * )
 */
class ThunderXymaticSchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    parent::registerResolvers($registry);

    $this->resolveFields();
  }

  /**
   * Add image media field resolvers.
   */
  protected function resolveFields(): void {
    // Video.
    $this->resolveMediaInterfaceFields('MediaXymatic');

    $this->addFieldResolverIfNotExists('MediaXymatic', 'src',
      $this->builder->produce('media_source_field')->map('media', $this->builder->fromParent())
    );
  }

}
