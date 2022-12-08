<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

/**
 * The Thunder VG Wort schema extension.
 *
 * @SchemaExtension(
 *   id = "thunder_vgwort",
 *   name = "Thunder VG Wort extension",
 *   description = "Adds vgWort field to article, depends on vgwort extension.",
 *   schema = "thunder"
 * )
 */
class ThunderVgWortSchemaExtension extends ThunderSchemaExtensionPluginBase {

    /**
    * {@inheritdoc}
    */
    public function registerResolvers(ResolverRegistryInterface $registry): void {
      parent::registerResolvers($registry);

      $this->addFieldResolverIfNotExists('Article', 'vgWort',
        $this->builder->produce('vgwort')
          ->map('entity', $this->builder->fromParent())
      );

    }

}
