<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;

/**
 * Extension to add the JSON-LD script tag query.
 *
 * @SchemaExtension(
 *   id = "thunder_jsonld",
 *   name = "JSON-LD extension",
 *   description = "Adds the JSON-LD script tag query.",
 *   schema = "thunder"
 * )
 */
class ThunderJsonLdSchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    parent::registerResolvers($registry);

    $this->addFieldResolverIfNotExists('Query', 'jsonld',
      $this->builder->produce('thunder_entity_sub_request')
        ->map('path', $this->builder->fromArgument('path'))
        ->map('key', $this->builder->fromValue('jsonld')
      )
    );
  }

}
