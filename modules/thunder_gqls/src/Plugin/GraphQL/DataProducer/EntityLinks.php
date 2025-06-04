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
 *   produces = @ContextDefinition("map",
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
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * EntityLinks constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    protected readonly RendererInterface $renderer,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
  public function resolve(EntityInterface $entity): array {
    $context = new RenderContext();
    $result = $this->renderer->executeInRenderContext($context, function () use ($entity): array {
      $links = $entity->getEntityType()->getLinkTemplates();

      array_walk($links, function (&$url, $rel) use ($entity): void {
        $url = '';
        try {
          $urlObject = $entity->toUrl($rel);
          if ($urlObject->access()) {
            $url = $urlObject->toString();
          }
        }
        catch (\Exception $exception) {
        }
      });

      $transformed_keys = array_map([$this, 'toCamelCase'], array_keys($links));
      return array_combine($transformed_keys, $links);
    });
    return $result ?? [];
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
  public static function toCamelCase(string $input): string {
    return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $input))));
  }

}
