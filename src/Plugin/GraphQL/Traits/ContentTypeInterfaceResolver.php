<?php

namespace Drupal\thunder_schema\Plugin\GraphQL\Traits;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;

trait ContentTypeInterfaceResolver {

  /**
   * @param string $type
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  public function addContentTypeInterfaceFields($type, ResolverRegistryInterface $registry, ResolverBuilder $builder) {

    $registry->addFieldResolver($type, 'uuid',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver($type, 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver($type, 'bundle',
      $builder->produce('entity_bundle')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver($type, 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver($type, 'url',
      $builder->compose(
        $builder->produce('entity_url')
          ->map('entity', $builder->fromParent()),
        $builder->produce('url_path')
          ->map('url', $builder->fromParent())
      )
    );

    $registry->addFieldResolver($type, 'created',
      $builder->produce('entity_created')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver($type, 'changed',
      $builder->produce('entity_changed')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver($type, 'language',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('langcode.value'))
    );

  }

}
