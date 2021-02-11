<?php

namespace Drupal\thunder_schema\Plugin\GraphQL\Traits;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;

trait ContentElementInterfaceResolver {

  /**
   * @param string $type
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  public function addContentElementInterfaceFields(string $type, ResolverRegistryInterface $registry, ResolverBuilder $builder) {

    $registry->addFieldResolver($type, 'uuid',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver($type, 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver($type, 'type',
      $builder->produce('entity_bundle')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver($type, 'label',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

  }

}
