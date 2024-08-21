<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\thunder_gqls\Wrappers\SearchApiResponse;

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
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    parent::registerResolvers($registry);

    $this->addFieldResolverIfNotExists('SearchApiResult', 'total',
      $this->builder->callback(function (SearchApiResponse $result) {
        return $result->total();
      })
    );

    $this->addFieldResolverIfNotExists('SearchApiResult', 'items',
      $this->builder->callback(function (SearchApiResponse $result) {
        return $result->items();
      })
    );
  }

}
