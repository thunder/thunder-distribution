<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\FileInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Returns image meta data.
 *
 * @DataProducer(
 *   id = "thunder_image",
 *   name = @Translation("Image meta data"),
 *   description = @Translation("Returns the meta data of an image entity."),
 *   produces = @ContextDefinition("any",
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
   * The rendering service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The image factory.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

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
      $container->get('image.factory'),
      $container->get('file_url_generator')
    );
  }

  /**
   * ImageUrl constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
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
    $pluginId,
    $pluginDefinition,
    RendererInterface $renderer,
    ImageFactory $imageFactory,
    FileUrlGeneratorInterface $fileUrlGenerator
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->renderer = $renderer;
    $this->imageFactory = $imageFactory;
    $this->fileUrlGenerator = $fileUrlGenerator;
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
  public function resolve(FileInterface $entity, array $field, RefinableCacheableDependencyInterface $metadata) {
    $access = $entity->access('view', NULL, TRUE);
    $metadata->addCacheableDependency($access);
    if ($access->isAllowed()) {
      $context = new RenderContext();
      $imageFactory = $this->imageFactory;

      $data = $this->renderer->executeInRenderContext($context, function () use ($entity, $imageFactory, $field) {
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
