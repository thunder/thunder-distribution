<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\schema_metatag\SchemaMetatagManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves a JSON-LD script tag for an entity.
 *
 * @DataProducer(
 *   id = "thunder_jsonld",
 *   name = @Translation("JSON-LD"),
 *   description = @Translation("Resolves a JSON-LD script tag for an entity."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("JSON-LD script tag")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Root value")
 *     )
 *   }
 * )
 */
class JsonLd extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The rendering service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The metatag manager service.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('metatag.manager')
    );
  }

  /**
   * JsonLd constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\metatag\MetatagManagerInterface $metatagManager
   *   The metatag manager service.
   */
  public function __construct(
    array $configuration,
    string $pluginId,
    $pluginDefinition,
    RendererInterface $renderer,
    ModuleHandlerInterface $moduleHandler,
    MetatagManagerInterface $metatagManager
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->renderer = $renderer;
    $this->metatagManager = $metatagManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Resolve the JSON-LD script tag string.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cacheable dependency interface.
   *
   * @return mixed
   *   The JSON-LD script tag.
   */
  public function resolve(EntityInterface $entity, RefinableCacheableDependencyInterface $metadata) {
    if (!$this->moduleHandler->moduleExists('schema_metatag') || !($entity instanceof ContentEntityInterface)) {
      return '';
    }
    $context = new RenderContext();
    $result = $this->renderer->executeInRenderContext($context, function () use ($entity) {
      return $this->getRenderedJsonld($entity);
    });

    if (!$context->isEmpty()) {
      $metadata->addCacheableDependency($context->pop());
    }

    return $result ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderedJsonld($entity = NULL, $entity_type = NULL) {
    // If nothing was passed in, assume the current entity.
    // @see schema_metatag_entity_load() to understand why this works.
    if (empty($entity)) {
      $entity = metatag_get_route_entity();
    }
    // Get all the metatags for this entity.
    if (!empty($entity) && $entity instanceof ContentEntityInterface) {
      foreach ($this->metatagManager->tagsFromEntityWithDefaults($entity) as $tag => $data) {
        $metatags[$tag] = $data;
      }
    }

    // Trigger hook_metatags_alter().
    // Allow modules to override tags or the entity used for token replacements.
    $context = ['entity' => $entity];
    $this->moduleHandler->alter('metatags', $metatags, $context);
    $elements = $this->metatagManager->generateElements($metatags, $entity);

    // Parse the Schema.org metatags out of the array.
    if ($items = SchemaMetatagManager::parseJsonld($elements['#attached']['html_head'])) {

      // Encode the Schema.org metatags as JSON LD.
      if ($jsonld = SchemaMetatagManager::encodeJsonld($items)) {
        // Pass back the rendered result.
        $html = SchemaMetatagManager::renderArrayJsonLd($jsonld);
        return $this->renderer->render($html);
      }
    }
  }

}
