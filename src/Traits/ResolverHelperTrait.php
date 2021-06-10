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

  /**
   * Produces an entity_reference field.
   *
   * @param string $field
   *   Name of the filed.
   * @param \Drupal\graphql\GraphQL\Resolver\ResolverInterface|null $entity
   *   Entity to get the field property.
   *
   * @return \Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerProxy
   *   The field data producer.
   */
  public function fromEntityReference(string $field, ResolverInterface $entity = NULL) {
    return $this->builder->produce('entity_reference')
      ->map('field', $this->builder->fromValue($field))
      ->map('entity', $entity ?: $this->builder->fromParent());
  }

  /**
   * Produces an entity_reference_revisions field.
   *
   * @param string $field
   *   Name of the filed.
   * @param \Drupal\graphql\GraphQL\Resolver\ResolverInterface|null $entity
   *   Entity to get the field property.
   *
   * @return \Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerProxy
   *   The field data producer.
   */
  public function fromEntityReferenceRevisions(string $field, $entity = NULL) {
    return $this->builder->produce('entity_reference_revisions')
      ->map('field', $this->builder->fromValue($field))
      ->map('entity', $entity ?: $this->builder->fromParent());
  }

}
