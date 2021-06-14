<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\Schema;

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

  const REQUIRED_EXTENSIONS = [
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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
  protected function setDataProducerManager(DataProducerPluginManager $pluginManager) {
    $this->dataProducerManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
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
  protected function getExtensions() {
    return array_map(function ($id) {
      return $this->extensionManager->createInstance($id);
    }, array_unique(array_filter($this->getConfiguration()['extensions']) + static::REQUIRED_EXTENSIONS));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    foreach (Element::children($form['extensions']) as $extension) {
      if (in_array($extension, static::REQUIRED_EXTENSIONS)) {
        $form['extensions'][$extension]['#access'] = FALSE;
        unset($form['extensions']['#options'][$extension]);
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSchemaDefinition() {
    return SdlSchemaPluginBase::getSchemaDefinition();
  }

  /**
   * Resolve custom types, that are used in multiple places.
   */
  private function resolveBaseTypes() {
    $this->addFieldResolverIfNotExists('Link', 'url',
      $this->builder->callback(function ($parent) {
        if (!empty($parent) && isset($parent['uri'])) {
          $urlObject = Url::fromUri($parent['uri']);
          $url = $urlObject->toString(TRUE)->getGeneratedUrl();
        }
        return $url ?? '';
      })
    );

    $this->addFieldResolverIfNotExists('Link', 'title',
      $this->builder->callback(function ($parent) {
        return $parent['title'];
      })
    );
  }

}
