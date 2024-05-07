<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
   * The HTTP kernel service.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The rendering service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

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
      $container->get('http_kernel'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('renderer')
    );
  }

  /**
   * ThunderEntitySubRequestBase constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The HTTP kernel service.
   * @param \Symfony\Component\HttpFoundation\Request $currentRequest
   *   The current request.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    array $configuration,
    string $pluginId,
    $pluginDefinition,
    HttpKernelInterface $httpKernel,
    Request $currentRequest,
    RendererInterface $renderer,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->httpKernel = $httpKernel;
    $this->currentRequest = $currentRequest;
    $this->renderer = $renderer;
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
    if (!str_starts_with($path, '/')) {
      $path = '/' . $path;
    }

    $url = $this->currentRequest->getSchemeAndHttpHost() . $path;
    $request = Request::create(
      $url,
      'GET',
      $this->currentRequest->query->all(),
      $this->currentRequest->cookies->all(),
      $this->currentRequest->files->all(),
      $this->currentRequest->server->all()
    );

    $response = $this->httpKernel->handle($request);
    $status = $response->getStatusCode();

    if ($response->isRedirect()){
      $url = $response->headers->get('location');
    }

    // 4xx-response should get the 4xx-response cache tag.
    if ($status >= 400 && $status < 500) {
      $metadata->addCacheTags(['4xx-response']);
    }

    return [
      'url' => $url,
      'status' => $response->getStatusCode(),
    ];
  }

}
