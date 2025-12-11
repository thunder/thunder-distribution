<?php

declare(strict_types=1);

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads a node entity from a preview path.
 */
#[DataProducer(
  id: "thunder_node_preview",
  name: new TranslatableMarkup("Load preview node"),
  description: new TranslatableMarkup("Loads a node entity from a preview path (node/preview/{uuid}/{view_mode})."),
  produces: new ContextDefinition(
    data_type: "entity",
    label: new TranslatableMarkup("Entity"),
  ),
  consumes: [
    "path" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("Path"),
    ),
  ],
)]
class ThunderNodePreview extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tempstore.private'),
    );
  }

  /**
   * The constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The tempstore.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    protected PrivateTempStoreFactory $tempStoreFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Resolver.
   *
   * Parses a preview path like `node/preview/{uuid}/{view_mode}` and returns
   * the preview node entity stored in the private tempstore.
   */
  public function resolve(string $path, RefinableCacheableDependencyInterface $context): mixed {
    // Disable caching for user-specific preview content.
    $context->mergeCacheMaxAge(0);

    $parts = explode('/', trim($path, '/'));
    if (count($parts) < 3 || $parts[0] !== 'node' || $parts[1] !== 'preview') {
      return NULL;
    }

    $uuid = $parts[2];
    $store = $this->tempStoreFactory->get('node_preview');
    $form_state = $store->get($uuid);
    if ($form_state) {
      /** @var \Drupal\Core\Entity\EntityInterface|null $entity */
      $entity = $form_state->getFormObject()->getEntity();
      return $entity ?: NULL;
    }

    return NULL;
  }

}
