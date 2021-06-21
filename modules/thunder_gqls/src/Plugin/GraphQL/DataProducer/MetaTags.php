<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\typed_data\DataFetcherTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves a typed data value at a given property path.
 *
 * @DataProducer(
 *   id = "thunder_metatags",
 *   name = @Translation("Metatags"),
 *   description = @Translation("Resolves metatags."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Metatag values")
 *   ),
 *   consumes = {
 *     "value" = @ContextDefinition("any",
 *       label = @Translation("Root value")
 *     ),
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Root type"),
 *       required = FALSE
 *     )
 *   }
 * )
 */
class MetaTags extends DataProducerPluginBase implements ContainerFactoryPluginInterface {
  use TypedDataTrait;
  use DataFetcherTrait;

  /**
   * The rendering service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The metatag manager service.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
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
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('renderer'),
      $container->get('metatag.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * MetaTags constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\metatag\MetatagManagerInterface $metatagManager
   *   The metatag manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    string $pluginId,
    $pluginDefinition,
    RendererInterface $renderer,
    MetatagManagerInterface $metatagManager,
    ModuleHandlerInterface $moduleHandler
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->renderer = $renderer;
    $this->metatagManager = $metatagManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Resolve the metadata.
   *
   * @param mixed $value
   *   The root value.
   * @param string|null $type
   *   The root type.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cacheable dependency interface.
   *
   * @return mixed
   *   Normalized metatags.
   */
  public function resolve($value, ?string $type, RefinableCacheableDependencyInterface $metadata) {
    if ($value instanceof ContentEntityInterface) {
      $context = new RenderContext();
      $result = $this->renderer->executeInRenderContext($context, function () use ($value) {
        $tags = $this->metatagManager->tagsFromEntityWithDefaults($value);

        // Trigger hook_metatags_attachments_alter().
        // Allow modules to rendered metatags prior to attaching.
        $this->moduleHandler->alter('metatags_attachments', $tags);

        // Filter non schema metatags, because schema metatags are processed in
        // EntitySchemaMetatags class.
        $elements = $this->metatagManager->generateRawElements($tags, $value);
        $elements = array_filter(
          $elements,
          function ($metatag_object) {
            return !NestedArray::getValue(
              $metatag_object,
              [
                '#attributes',
                'schema_metatag',
              ]
            );
          }
        );

        $data = [];
        foreach ($elements as $element) {
          $data[] = [
            'tag' => $element['#tag'],
            'attributes' => Json::encode($element['#attributes']),
          ];
        }

        return $data;
      });

      if (!$context->isEmpty()) {
        $metadata->addCacheableDependency($context->pop());
      }
    }

    return $result ?? NULL;
  }

}
