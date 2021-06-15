<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\DataProducerPluginManager;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerProxy;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\user\EntityOwnerInterface;
use Drupal\thunder_gqls\Traits\ResolverHelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The base class for Thunder schema extension plugins.
 */
abstract class ThunderSchemaExtensionPluginBase extends SdlSchemaExtensionPluginBase {

  use ResolverHelperTrait;

  /**
   * The data producer plugin manager.
   *
   * @var \Drupal\graphql\Plugin\DataProducerPluginManager
   */
  protected $dataProducerManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->createResolverBuilder();
    $plugin->setDataProducerManager($container->get('plugin.manager.graphql.data_producer'));
    $plugin->setEntityTypeManager($container->get('entity_type.manager'));
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    $this->registry = $registry;
  }

  /**
   * Set the plugin manager.
   *
   * @param \Drupal\graphql\Plugin\DataProducerPluginManager $pluginManager
   *   The data producer plugin manager.
   */
  protected function setDataProducerManager(DataProducerPluginManager $pluginManager) {
    $this->dataProducerManager = $pluginManager;
  }

  /**
   * Set the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  protected function setEntityTypeManager(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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
   * @param string $entity_type_id
   *   The entity type ID.
   */
  protected function resolveBaseFields(string $type, string $entity_type_id) {
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

    $this->addFieldResolverIfNotExists($type, 'created',
      $this->builder->produce('entity_created')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists($type, 'language',
      $this->builder->fromPath('entity', 'langcode.value')
    );

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if ($entity_type->hasLinkTemplate('canonical')) {
      $this->addFieldResolverIfNotExists($type, 'url',
        $this->builder->compose(
          $this->builder->produce('entity_url')
            ->map('entity', $this->builder->fromParent()),
          $this->builder->produce('url_path')
            ->map('url', $this->builder->fromParent())
        )
      );
    }

    if ($entity_type->entityClassImplements(EntityChangedInterface::class)) {
      $this->addFieldResolverIfNotExists($type, 'changed',
        $this->builder->produce('entity_changed')
          ->map('entity', $this->builder->fromParent())
      );
    }

    if ($entity_type->entityClassImplements(EntityPublishedInterface::class)) {
      $this->addFieldResolverIfNotExists($type, 'published',
        $this->builder->produce('entity_published')
          ->map('entity', $this->builder->fromParent())
      );
    }

    if ($entity_type->entityClassImplements(EntityOwnerInterface::class)) {
      $this->addFieldResolverIfNotExists($type, 'author',
        $this->builder->produce('entity_owner')
          ->map('entity', $this->builder->fromParent())
      );
    }
  }

  /**
   * Add fields common to all media types.
   *
   * @param string $type
   *   The type name.
   */
  protected function resolveMediaInterfaceFields(string $type) {
    $this->resolveBaseFields($type, 'media');

    $this->addFieldResolverIfNotExists($type, 'thumbnail',
      $this->builder->produce('thunder_image')
        ->map('entity', $this->builder->fromPath('entity', 'thumbnail.entity'))
        ->map('field', $this->builder->fromPath('entity', 'thumbnail'))
    );

    if ($this->dataProducerManager->hasDefinition('media_expire_fallback_entity')) {
      $this->addFieldResolverIfNotExists($type, 'fallbackMedia',
        $this->builder->produce('media_expire_fallback_entity')
          ->map('entity', $this->builder->fromParent())
      );
    }
  }

  /**
   * Add fields common to all page types.
   *
   * @param string $type
   *   The type name.
   * @param string $entity_type_id
   *   The entity type ID.
   */
  protected function resolvePageInterfaceFields(string $type, string $entity_type_id) {
    $this->resolveBaseFields($type, $entity_type_id);

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
   * Add content query field resolvers.
   *
   * @param string $page_type
   *   The page type name.
   * @param string $entity_type_id
   *   The entity type ID.
   */
  protected function resolvePageInterfaceQueryFields(string $page_type, string $entity_type_id) {
    $this->addFieldResolverIfNotExists('Query', $page_type,
      $this->builder->produce('entity_load_by_uuid')
        ->map('type', $this->builder->fromValue($entity_type_id))
        ->map('uuid', $this->builder->fromArgument('uuid'))
    );
  }

}
