<?php

namespace Drupal\thunder_schema\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerProxy;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ThunderSchemaExtensionPluginBase extends SdlSchemaExtensionPluginBase {

  /**
   * ResolverRegistryInterface.
   *
   * @var \Drupal\graphql\GraphQL\ResolverRegistryInterface
   */
  protected $registry;

  /**
   * ResolverBuilder.
   *
   * @var \Drupal\graphql\GraphQL\ResolverBuilder
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->createResolverBuilder();
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $this->registry = $registry;
  }

  /**
   * Create the ResolverBuilder.
   */
  protected function createResolverBuilder() {
    $this->builder = new ResolverBuilder();
  }

  /**
   * Takes the bundle name and returns the schema name.
   *
   * @param string $bundleName
   *   The bundle name.
   *
   * @return string
   *   Returns the mapped bundle name.
   */
  protected function mapBundleToSchemaName(string $bundleName) {
    return str_replace('_', '', ucwords($bundleName, '_'));
  }

  /**
   * Add fields common to all entities.
   *
   * @param string $type
   *   The type name.
   */
  protected function resolveBaseFields(string $type) {
    $this->registry->addFieldResolver(
      $type,
      'uuid',
      $this->builder->produce('entity_uuid')
        ->map('entity', $this->builder->fromParent())
    );

    $this->registry->addFieldResolver(
      $type,
      'id',
      $this->builder->produce('entity_id')
        ->map('entity', $this->builder->fromParent())
    );

    $this->registry->addFieldResolver(
      $type,
      'entity',
      $this->builder->produce('entity_type_id')
        ->map('entity', $this->builder->fromParent())
    );

    $this->registry->addFieldResolver(
      $type,
      'name',
      $this->builder->produce('entity_label')
        ->map('entity', $this->builder->fromParent())
    );
  }


  /**
   * Add fields common to all page types.
   *
   * @param string $type
   *   The type name.
   */
  protected function resolvePageInterfaceFields(string $type) {
    $this->resolveBaseFields($type);

    $this->registry->addFieldResolver($type, 'url',
      $this->builder->compose(
        $this->builder->produce('entity_url')
          ->map('entity', $this->builder->fromParent()),
        $this->builder->produce('url_path')
          ->map('url', $this->builder->fromParent())
      )
    );

    $this->registry->addFieldResolver($type, 'created',
      $this->builder->produce('entity_created')
        ->map('entity', $this->builder->fromParent())
    );

    $this->registry->addFieldResolver($type, 'changed',
      $this->builder->produce('entity_changed')
        ->map('entity', $this->builder->fromParent())
    );

    $this->registry->addFieldResolver($type, 'language',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('langcode.value'))
    );
  }

  /**
   * Get the data producer for a referenced entity.
   *
   * @param $parentEntityType
   *   The entity type id of the parent entity.
   * @param $referenceFieldName
   *   The reference field name.
   *
   * @return \Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerProxy
   *   The data producer proxy.
   */
  protected function referencedEntityProducer($parentEntityType, $referenceFieldName) : DataProducerProxy {
    return $this->builder->produce('property_path')
      ->map('type', $this->builder->fromValue('entity:' . $parentEntityType))
      ->map('value', $this->builder->fromParent())
      ->map('path', $this->builder->fromValue($referenceFieldName . '.entity'));
  }

}
