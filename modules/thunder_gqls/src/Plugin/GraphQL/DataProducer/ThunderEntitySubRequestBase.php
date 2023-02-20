<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql\SubRequestResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;

/**
 * A base class for data producer that need to do a subrequest.
 */
abstract class ThunderEntitySubRequestBase extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

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
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->httpKernel = $httpKernel;
    $this->currentRequest = $currentRequest;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveField(FieldContext $fieldContext) {
    $contextValues = $this->getContextValues();

    if (!isset($contextValues['path'])) {
      throw new \LogicException('Missing required path argument.');
    }

    $url = $this->currentRequest->getSchemeAndHttpHost() . $contextValues['path'];
    $request = $this->createRequest($this->currentRequest, $url, $fieldContext);

    $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);
    if ($response instanceof SubRequestResponse) {
      return $response->getResult();
    }

    $produces = $this->getPluginDefinition()['produces'];
    $dataDefinition = $produces->getDataDefinition();

    return $produces->getTypedDataManager()->create($dataDefinition)->getValue();
  }

  /**
   * Create a sub-request for the given url.
   *
   * @param \Symfony\Component\HttpFoundation\Request $current
   *   The current main request.
   * @param string $url
   *   The url to run the subrequest on.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $fieldContext
   *   The field context.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request object.
   */
  protected function createRequest(Request $current, string $url, FieldContext $fieldContext) {
    $request = Request::create(
      $url,
      'GET',
      $current->query->all(),
      $current->cookies->all(),
      $current->files->all(),
      $current->server->all()
    );

    $request->attributes->set('_graphql_subrequest', function (CacheableMetadata $cacheableMetadata) use ($fieldContext) {
      if (!method_exists($this, 'resolve')) {
        throw new \LogicException('Missing data producer resolve method.');
      }

      $context = new RenderContext();
      $contextValues = $this->getContextValues();
      $result = $this->renderer->executeInRenderContext($context, fn() => call_user_func_array(
        [$this, 'resolve'],
        array_values(array_merge($contextValues, [
          $cacheableMetadata,
          $fieldContext,
        ]))
      ));

      if (!$context->isEmpty()) {
        $fieldContext->addCacheableDependency($context->pop());
      }

      return $result ?? '';
    });

    if ($current->hasSession()) {
      $request->setSession($current->getSession());
    }

    return $request;
  }

}
