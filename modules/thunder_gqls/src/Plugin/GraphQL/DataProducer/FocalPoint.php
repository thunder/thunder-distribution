<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\FileInterface;
use Drupal\focal_point\FocalPointManagerInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves the focal point positions for a file.
 *
 * @DataProducer(
 *   id = "focal_point",
 *   name = @Translation("FocalPoint"),
 *   description = @Translation("Resolves the focal point positions for a file."),
 *   produces = @ContextDefinition("map",
 *     label = @Translation("Focal point positions")
 *   ),
 *   consumes = {
 *     "file" = @ContextDefinition("entity",
 *       label = @Translation("Root value")
 *     )
 *   }
 * )
 */
class FocalPoint extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The focal point config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

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
      $container->get('config.factory'),
      $container->get('focal_point.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * FocalPoint constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\focal_point\FocalPointManagerInterface $focalPointManager
   *   The focal point manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $configFactory,
    protected readonly FocalPointManagerInterface $focalPointManager,
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $configFactory->get('focal_point.settings');
  }

  /**
   * Resolve the focal point positions.
   *
   * @param \Drupal\file\FileInterface $file
   *   The entity.
   *
   * @return array
   *   The focal point position tag.
   */
  public function resolve(FileInterface $file): array {
    if (!$this->moduleHandler->moduleExists('focal_point')) {
      return ['x' => NULL, 'y' => NULL];
    }
    return $this->focalPointManager->getCropEntity($file, $this->config->get('crop_type'))->position();
  }

}
