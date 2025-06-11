<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\graphql\SubRequestResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * A base class for data producer that need to do a subrequest.
 */
abstract class ThunderEntitySubRequestBase extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

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
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
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
    string $plugin_id,
    $plugin_definition,
    protected readonly HttpKernelInterface $httpKernel,
    protected readonly Request $currentRequest,
    protected readonly RendererInterface $renderer,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function resolveField(FieldContext $field): mixed {
    $contextValues = $this->getContextValues();

    if (!isset($contextValues['path'])) {
      throw new \LogicException('Missing required path argument.');
    }

    $url = $this->currentRequest->getSchemeAndHttpHost() . $contextValues['path'];
    $request = $this->createRequest($this->currentRequest, $url, $field);

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
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $field
   *   The field context.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The request object.
   */
  protected function createRequest(Request $current, string $url, FieldContext $field): Request {
    $request = Request::create(
      $url,
      'GET',
      $current->query->all(),
      $current->cookies->all(),
      $current->files->all(),
      $current->server->all()
    );

    $request->attributes->set('_graphql_subrequest', function (CacheableMetadata $cacheableMetadata) use ($field) {
      if (!method_exists($this, 'resolve')) {
        throw new \LogicException('Missing data producer resolve method.');
      }

      $context = new RenderContext();
      $contextValues = $this->getContextValues();
      $result = $this->renderer->executeInRenderContext($context, fn() => call_user_func_array(
        [$this, 'resolve'],
        array_values(array_merge($contextValues, [
          $cacheableMetadata,
          $field,
        ]))
      ));

      if (!$context->isEmpty()) {
        $field->addCacheableDependency($context->pop());
      }

      return $result ?? '';
    });

    if ($current->hasSession()) {
      $request->setSession($current->getSession());
    }

    return $request;
  }

}
