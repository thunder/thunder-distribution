<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use GraphQL\Language\Source;

/**
 * The search api query schema extension.
 *
 * @SchemaExtension(
 *   id = "thunder_search_api",
 *   name = "Search API extension",
 *   description = "Adds search api queries.",
 *   schema = "thunder"
 * )
 */
class ThunderSearchApiSchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getExtensionDefinition(): ?Source {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    parent::registerResolvers($registry);

    $this->addFieldResolverIfNotExists('SearchApiResult', 'total',
      $this->builder->produce('thunder_entity_list_total')
        ->map('list', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists('SearchApiResult', 'items',
      $this->builder->produce('thunder_entity_list_items')
        ->map('list', $this->builder->fromParent())
    );
  }

}
