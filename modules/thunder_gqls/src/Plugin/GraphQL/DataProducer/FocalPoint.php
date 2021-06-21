<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ConfigFactoryInterface;
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
 *   produces = @ContextDefinition("any",
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
  protected $config;

  /**
   * The focal point manager service.
   *
   * @var \Drupal\focal_point\FocalPointManagerInterface
   */
  protected $focalPointManager;

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
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
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
    string $pluginId,
    $pluginDefinition,
    ConfigFactoryInterface $configFactory,
    FocalPointManagerInterface $focalPointManager,
    ModuleHandlerInterface $moduleHandler
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->config = $configFactory->get('focal_point.settings');
    $this->focalPointManager = $focalPointManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Resolve the focal point positions.
   *
   * @param \Drupal\file\FileInterface $file
   *   The entity.
   *
   * @return mixed
   *   The focal point position tag.
   */
  public function resolve(FileInterface $file) {
    if (!$this->moduleHandler->moduleExists('focal_point')) {
      return '';
    }
    return $this->focalPointManager->getCropEntity($file, $this->config->get('crop_type'))->position();
  }

}
