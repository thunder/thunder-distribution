<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
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
 *   produces = @ContextDefinition("map",
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
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
      $container->get('path.validator'),
      $container->get('redirect.repository', ContainerInterface::NULL_ON_INVALID_REFERENCE)
    );
  }

  /**
   * Route constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The path validator.
   * @param \Drupal\redirect\RedirectRepository|null $redirectRepository
   *   The redirect repository.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly PathValidatorInterface $pathValidator,
    protected readonly ?RedirectRepository $redirectRepository = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
  public function resolve(string $path, RefinableCacheableDependencyInterface $metadata): array {
    $metadata->addCacheTags(['redirect_list']);

    if ($this->redirectRepository) {
      $queryString = parse_url($path, PHP_URL_QUERY) ?: '';
      $pathWithoutQuery = parse_url($path, PHP_URL_PATH) ?: $path;

      $language = $this->languageManager->getCurrentLanguage()->getId();
      $queryParameters = [];

      parse_str($queryString, $queryParameters);

      /** @var \Drupal\redirect\Entity\Redirect|null $redirect */
      $redirect = $this->redirectRepository->findMatchingRedirect(
        $pathWithoutQuery,
        $queryParameters,
        $language
      );

      if ($redirect instanceof Redirect) {
        $urlObject = $redirect->getRedirectUrl();
        $metadata->addCacheTags($redirect->getCacheTags());

        $redirectUri = $urlObject->toString(TRUE)->getGeneratedUrl();
        return [
          'url' => $redirectUri . (!empty($queryString) ? '?' . $queryString : ''),
          'status' => $redirect->getStatusCode(),
        ];
      }
    }

    if (($url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($path)) && $url->isRouted()) {

      if ($url->access()) {
        return [
          'url' => $path,
          'status' => 200,
        ];
      }
      else {
        $metadata->addCacheTags(['4xx-response']);
        return [
          'url' => $path,
          'status' => 403,
        ];
      }
    }

    $metadata->addCacheTags(['4xx-response']);
    return [
      'url' => $path,
      'status' => 404,
    ];
  }

}
