<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\Plugin\DataProducerPluginManager;
use Drupal\graphql\Plugin\GraphQL\Schema\ComposableSchema;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\thunder_gqls\Traits\ResolverHelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tha base schema for Thunder composable schema.
 *
 * @Schema(
 *   id = "thunder",
 *   name = "Thunder composable schema"
 * )
 */
class ThunderSchema extends ComposableSchema {

  use ResolverHelperTrait;

  public const REQUIRED_EXTENSIONS = [
    'thunder_pages',
    'thunder_media',
    'thunder_paragraphs',
  ];

  /**
   * The data producer plugin manager.
   *
   * @var \Drupal\graphql\Plugin\DataProducerPluginManager
   */
  protected $dataProducerManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $schema = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $schema->setDataProducerManager($container->get('plugin.manager.graphql.data_producer'));
    return $schema;
  }

  /**
   * Set the plugin manager.
   *
   * @param \Drupal\graphql\Plugin\DataProducerPluginManager $pluginManager
   *   The data producer plugin manager.
   */
  protected function setDataProducerManager(DataProducerPluginManager $pluginManager): void {
    $this->dataProducerManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry(): ResolverRegistryInterface {
    $this->registry = new ResolverRegistry();
    $this->createResolverBuilder();

    $this->resolveBaseTypes();

    $this->addFieldResolverIfNotExists('Query', 'redirect',
      $this->builder->produce('thunder_redirect')
        ->map('path', $this->builder->fromArgument('path'))
    );

    if ($this->dataProducerManager->hasDefinition('access_unpublished_token_set')) {
      $this->addFieldResolverIfNotExists('Query', 'accessUnpublishedToken',
        $this->builder->produce('access_unpublished_token_set')
          ->map('token', $this->builder->fromArgument('auHash'))
      );
    }

    return $this->registry;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtensions(): array {
    return array_map(fn($id): object => $this->extensionManager->createInstance($id), array_unique(array_merge(array_filter($this->getConfiguration()['extensions']), static::REQUIRED_EXTENSIONS)));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    foreach (Element::children($form['extensions']) as $extension) {
      if (in_array($extension, static::REQUIRED_EXTENSIONS)) {
        $form['extensions'][$extension]['#disabled'] = TRUE;
        $form['extensions'][$extension]['#value'] = TRUE;
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSchemaDefinition(): string {
    return SdlSchemaPluginBase::getSchemaDefinition();
  }

  /**
   * Resolve custom types, that are used in multiple places.
   */
  private function resolveBaseTypes(): void {
    $this->addFieldResolverIfNotExists('Link', 'url',
      $this->builder->callback(function ($parent) {
        if (!empty($parent) && isset($parent['uri'])) {
          $urlObject = Url::fromUri($parent['uri']);
          $url = $urlObject->toString(TRUE)->getGeneratedUrl();
        }
        return $url ?? '';
      })
    );

    $this->addSimpleCallbackFields('Link', ['title']);
    $this->addSimpleCallbackFields('FocalPoint', ['x', 'y']);
    $this->addSimpleCallbackFields('Redirect', ['url', 'status']);
    $this->addSimpleCallbackFields('EntityLinks', [
      'canonical', 'deleteForm', 'deleteMultipleForm', 'editForm',
      'versionHistory', 'revision', 'create', 'latestVersion',
    ]);
    $this->addSimpleCallbackFields('Thumbnail', [
      'src', 'width', 'height', 'alt', 'title',
    ]);
    $this->addSimpleCallbackFields('ImageDerivative', ['src', 'width', 'height']);
    $this->addSimpleCallbackFields('Schema', ['query']);
  }

}
