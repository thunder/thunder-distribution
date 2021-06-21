<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin to resolve all the links for an entity.
 *
 * @DataProducer(
 *   id = "entity_links",
 *   name = @Translation("Entity links"),
 *   description = @Translation("Returns the entity's links."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Links")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity")
 *     )
 *   }
 * )
 */
class EntityLinks extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The rendering service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

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
      $container->get('renderer')
    );
  }

  /**
   * EntityLinks constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    array $configuration,
    string $pluginId,
    $pluginDefinition,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->renderer = $renderer;
  }

  /**
   * Resolve all the links for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to act on.
   *
   * @return string[]
   *   The entity links.
   */
  public function resolve(EntityInterface $entity) {
    $context = new RenderContext();
    $result = $this->renderer->executeInRenderContext($context, function () use ($entity) {
      $links = $entity->getEntityType()->getLinkTemplates();

      array_walk($links, function (&$url, $rel) use ($entity) {
        try {
          $url = $entity->toUrl($rel)->toString();
        }
        catch (\Exception $exception) {
          $url = '';
        }
      });

      $transformed_keys = array_map([$this, 'toCamelCase'], array_keys($links));
      return array_combine($transformed_keys, $links);
    });
    return $result ?? NULL;
  }

  /**
   * Convert string to camel case.
   *
   * @param string $input
   *   Input string.
   *
   * @return string
   *   Camel case string.
   */
  public static function toCamelCase($input) {
    return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $input))));
  }

}
