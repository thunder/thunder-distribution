<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\redirect\Entity\Redirect;
use Drupal\redirect\RedirectRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the ID of current user.
 *
 * @DataProducer(
 *   id = "thunder_redirect",
 *   name = @Translation("Redirect"),
 *   description = @Translation("Redirect."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Redirect")
 *   ),
 *   consumes = {
 *     "path" = @ContextDefinition("string",
 *       label = @Translation("Path to redirect"),
 *       required = TRUE,
 *       default_value = ""
 *     ),
 *   }
 * )
 */
class ThunderRedirect extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Optional redirect module repository.
   *
   * @var \Drupal\redirect\RedirectRepository|null
   */
  protected $redirectRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

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
      $container->get('language_manager'),
      $container->get('redirect.repository', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );
  }

  /**
   * Route constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\redirect\RedirectRepository|null $redirectRepository
   *   The redirect repository.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    LanguageManagerInterface $languageManager,
    ?RedirectRepository $redirectRepository = NULL
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->languageManager = $languageManager;
    $this->redirectRepository = $redirectRepository;
  }

  /**
   * Resolver.
   *
   * @param string $path
   *   The url path.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The metadata.
   *
   * @return array
   *   The redirect data.
   */
  public function resolve(string $path, RefinableCacheableDependencyInterface $metadata) {
    if (!$this->redirectRepository) {
      return [];
    }
    $language = $this->languageManager->getCurrentLanguage()->getId();

    /** @var \Drupal\redirect\Entity\Redirect|null $redirect */
    $redirect = $this->redirectRepository->findMatchingRedirect($path, [], $language);
    if ($redirect instanceof Redirect) {
      $urlObject = $redirect->getRedirectUrl();
      $metadata->addCacheTags(
        array_merge($redirect->getCacheTags(), ['redirect_list'])
      );

      return [
        'url' => $urlObject->toString(TRUE)->getGeneratedUrl(),
        'status' => $redirect->getStatusCode(),
      ];
    }
  }

}
