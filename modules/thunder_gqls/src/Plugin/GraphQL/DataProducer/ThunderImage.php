<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\FileInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns image meta data.
 *
 * @DataProducer(
 *   id = "thunder_image",
 *   name = @Translation("Image meta data"),
 *   description = @Translation("Returns the meta data of an image entity."),
 *   produces = @ContextDefinition("map",
 *     label = @Translation("Metadata")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity")
 *     ),
 *    "field" = @ContextDefinition("any",
 *       label = @Translation("Field")
 *     )
 *   }
 * )
 */
class ThunderImage extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('image.factory'),
      $container->get('file_url_generator')
    );
  }

  /**
   * ImageUrl constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   The image factory.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly RendererInterface $renderer,
    protected readonly ImageFactory $imageFactory,
    protected readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Resolver.
   *
   * @param \Drupal\file\FileInterface $entity
   *   The file entity.
   * @param array $field
   *   Values of the field.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cacheable dependency interface.
   *
   * @return array
   *   The image meta data
   */
  public function resolve(FileInterface $entity, array $field, RefinableCacheableDependencyInterface $metadata): array {
    $access = $entity->access('view', NULL, TRUE);
    $metadata->addCacheableDependency($access);
    if ($access->isAllowed()) {
      $context = new RenderContext();
      $imageFactory = $this->imageFactory;

      $data = $this->renderer->executeInRenderContext($context, function () use ($entity, $imageFactory, $field): array {
        $uri = $entity->getFileUri();
        $image = $imageFactory->get($uri);
        $current_field = reset($field);
        return [
          'src' => $this->fileUrlGenerator->generateAbsoluteString($uri),
          'width' => $image->getWidth(),
          'height' => $image->getHeight(),
          'alt' => $current_field['alt'],
          'title' => $current_field['title'],
        ];
      });

      if (!$context->isEmpty()) {
        $metadata->addCacheableDependency($context->pop());
      }

      return $data;
    }

    return [];
  }

}
