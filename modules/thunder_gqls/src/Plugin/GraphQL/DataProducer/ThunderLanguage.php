<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Gets the language of the current path.
 *
 * @DataProducer(
 *   id = "thunder_language",
 *   name = @Translation("Language"),
 *   description = @Translation("Language."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Langcode")
 *   ),
 *   consumes = {
 *     "path" = @ContextDefinition("string",
 *       label = @Translation("Path"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class ThunderLanguage extends ThunderEntitySubRequestBase {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    $producer = parent::create($container, $configuration, $pluginId, $pluginDefinition);
    $producer->setLanguageManager($container->get('language_manager'));
    return $producer;
  }

  /**
   * Sets the language manager service.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager service.
   */
  protected function setLanguageManager(LanguageManagerInterface $languageManager): void {
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function doResolve(CacheableMetadata $cacheableMetadata, FieldContext $fieldContext) {
    return $this->languageManager->getCurrentLanguage()->getId();
  }

}
