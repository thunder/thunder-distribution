<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Url;
use Drupal\graphql\GraphQL\Resolver\ResolverInterface;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerProxy;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base class for Thunder schema extension plugins.
 */
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
    $this->addFieldResolverIfNotExists(
      $type,
      'uuid',
      $this->builder->produce('entity_uuid')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists(
      $type,
      'id',
      $this->builder->produce('entity_id')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists(
      $type,
      'entity',
      $this->builder->produce('entity_type_id')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists(
      $type,
      'name',
      $this->builder->produce('entity_label')
        ->map('entity', $this->builder->fromParent())
    );
  }

  /**
   * Add fields common to all media types.
   *
   * @param string $type
   *   The type name.
   */
  protected function resolveMediaInterfaceFields(string $type) {
    $this->resolveBaseFields($type);

    $this->addFieldResolverIfNotExists($type, 'thumbnail',
      $this->builder->produce('thunder_image')
        ->map('entity', $this->builder->fromPath('entity', 'thumbnail.entity'))
    );

    $this->resolveBaseTypes();
  }

  /**
   * Add fields common to all page types.
   *
   * @param string $type
   *   The type name.
   */
  protected function resolvePageInterfaceFields(string $type) {
    $this->resolveBaseFields($type);

    $this->addFieldResolverIfNotExists($type, 'url',
      $this->builder->compose(
        $this->builder->produce('entity_url')
          ->map('entity', $this->builder->fromParent()),
        $this->builder->produce('url_path')
          ->map('url', $this->builder->fromParent())
      )
    );

    $this->addFieldResolverIfNotExists($type, 'created',
      $this->builder->produce('entity_created')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists($type, 'changed',
      $this->builder->produce('entity_changed')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists($type, 'language',
      $this->builder->fromPath('entity', 'langcode.value')
    );

    $this->addFieldResolverIfNotExists($type, 'metatags',
      $this->builder->produce('thunder_metatags')
        ->map('type', $this->builder->fromValue('entity'))
        ->map('value', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists($type, 'entityLinks',
      $this->builder->produce('entity_links')
        ->map('entity', $this->builder->fromParent())
    );
  }

  /**
   * Get the data producer for a referenced entity.
   *
   * @param string $referenceFieldName
   *   The reference field name.
   *
   * @return \Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerProxy
   *   The data producer proxy.
   */
  protected function referencedEntityProducer(string $referenceFieldName) : DataProducerProxy {
    return $this->builder->fromPath('entity', $referenceFieldName . '.entity');
  }

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
   * Add content query field resolvers.
   *
   * @param string $page_type
   *   The page type name.
   * @param string $entity_type
   *   The entity type name.
   */
  protected function resolvePageInterfaceQueryFields(string $page_type, string $entity_type) {
    $this->addFieldResolverIfNotExists('Query', $page_type,
      $this->builder->produce('entity_load')
        ->map('type', $this->builder->fromValue($entity_type))
        ->map('bundles', $this->builder->fromValue([$page_type]))
        ->map('id', $this->builder->fromArgument('id'))
    );
  }

  /**
   * Resolve custom types, that are used in multiple places.
   */
  private function resolveBaseTypes() {
    $this->addFieldResolverIfNotExists('Link', 'url',
      $this->builder->callback(function ($parent) {
        if (!empty($parent) && isset($parent['uri'])) {
          $urlObject = Url::fromUri($parent['uri']);
          $url = $urlObject->toString(TRUE)->getGeneratedUrl();
        }
        return $url ?? '';
      })
    );

    $this->addFieldResolverIfNotExists('Link', 'title',
      $this->builder->callback(function ($parent) {
        return $parent['title'];
      })
    );
  }

}
