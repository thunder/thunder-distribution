<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the language of the current path.
 *
 * @DataProducer(
 *   id = "thunder_language",
 *   name = @Translation("Language"),
 *   description = @Translation("Language."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("Langcode")
 *   ),
 *   consumes = {
 *     "path" = @ContextDefinition("string",
 *       label = @Translation("Path"),
 *       required = TRUE
 *     ),
 *     "type" = @ContextDefinition("string",
 *       label = @Translation("Language type"),
 *       description = @Translation("The language type as defined in \Drupal\Core\Language\LanguageInterface. Defaults to url language."),
 *       default_value = \Drupal\Core\Language\LanguageInterface::TYPE_URL,
 *       required = FALSE
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
  protected LanguageManagerInterface $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $producer = parent::create($container, $configuration, $plugin_id, $plugin_definition);
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
   * Resolve language.
   *
   * @param string $path
   *   The path.
   * @param string $type
   *   The language type.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheableMetadata
   *   Cache metadata for the subrequest.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $fieldContext
   *   The field context of the data producer.
   *
   * @return string
   *   The resolved language code.
   */
  protected function resolve(string $path, string $type, CacheableMetadata $cacheableMetadata, FieldContext $fieldContext) : string {
    return $this->languageManager->getCurrentLanguage($type)->getId();
  }

}
