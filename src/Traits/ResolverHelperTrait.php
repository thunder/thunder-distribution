<?php

namespace Drupal\thunder_gqls\Traits;

use Drupal\graphql\GraphQL\Resolver\ResolverInterface;
use Drupal\graphql\GraphQL\ResolverBuilder;

/**
 * Helper functions for field resolvers.
 */
trait ResolverHelperTrait {

  /**
   * ResolverBuilder.
   *
   * @var \Drupal\graphql\GraphQL\ResolverBuilder
   */
  protected $builder;

  /**
   * ResolverRegistryInterface.
   *
   * @var \Drupal\graphql\GraphQL\ResolverRegistryInterface
   */
  protected $registry;

  /**
   * Add field resolver to registry, if it does not already exist.
   *
   * @param string $type
   *   The type name.
   * @param string $field
   *   The field name.
   * @param \Drupal\graphql\GraphQL\Resolver\ResolverInterface $resolver
   *   The field resolver.
   */
  protected function addFieldResolverIfNotExists(string $type, string $field, ResolverInterface $resolver) {
    if (!$this->registry->getFieldResolver($type, $field)) {
      $this->registry->addFieldResolver($type, $field, $resolver);
    }
  }

  /**
   * Create the ResolverBuilder.
   */
  protected function createResolverBuilder() {
    $this->builder = new ResolverBuilder();
  }

}
