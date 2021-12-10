<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\schema_metatag\SchemaMetatagManager;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Gets the JSON-LD script of the current path.
 *
 * @DataProducer(
 *   id = "thunder_jsonld",
 *   name = @Translation("JSON-LD"),
 *   description = @Translation("JSON-LD."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Script tag")
 *   ),
 *   consumes = {
 *     "path" = @ContextDefinition("string",
 *       label = @Translation("Path"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class ThunderJsonLd extends ThunderEntitySubRequestBase implements ContainerFactoryPluginInterface {

  /**
   * The metatag manager service.
   *
   * @var \Drupal\metatag\MetatagManager
   */
  protected $metatagManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    $producer = parent::create($container, $configuration, $pluginId, $pluginDefinition);
    $producer->setMetatagManager($container->get('metatag.manager'));
    $producer->setModuleHandler($container->get('module_handler'));
    return $producer;
  }

  /**
   * Sets the metatag manager.
   *
   * @param \Drupal\metatag\MetatagManagerInterface $metatagManager
   *   The metatag manager service.
   */
  protected function setMetatagManager(MetatagManagerInterface $metatagManager): void {
    $this->metatagManager = $metatagManager;
  }

  /**
   * Sets the module handler service.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  protected function setModuleHandler(ModuleHandlerInterface $moduleHandler): void {
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  protected function doResolve(CacheableMetadata $cacheableMetadata, FieldContext $fieldContext) {
    // If nothing was passed in, assume the current entity.
    // @see schema_metatag_entity_load() to understand why this works.
    if (!$this->metatagManager || !$this->moduleHandler->moduleExists('schema_metatag')) {
      return '';
    }

    $entity = metatag_get_route_entity();

    if (!($entity instanceof ContentEntityInterface)) {
      return '';
    }

    // Get all the metatags for this entity.
    $metatags = [];
    foreach ($this->metatagManager->tagsFromEntityWithDefaults($entity) as $tag => $data) {
      $metatags[$tag] = $data;
    }

    // Trigger hook_metatags_alter().
    // Allow modules to override tags or the entity used for token replacements.
    $context = ['entity' => $entity];
    $this->moduleHandler->alter('metatags', $metatags, $context);
    $elements = $this->metatagManager->generateElements($metatags, $entity);
    // Parse the Schema.org metatags out of the array.
    if ($items = SchemaMetatagManager::parseJsonld(
      $elements['#attached']['html_head']
    )) {
      // Encode the Schema.org metatags as JSON LD.
      if ($jsonld = SchemaMetatagManager::encodeJsonld($items)) {
        // Pass back the rendered result.
        $html = SchemaMetatagManager::renderArrayJsonLd($jsonld);
        return $this->renderer->render($html);
      }
    }
    return '';
  }

}
