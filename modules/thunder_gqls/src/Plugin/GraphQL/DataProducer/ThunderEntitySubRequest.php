<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Get data from a sub request to the URL of an entity.
 *
 * @DataProducer(
 *   id = "thunder_entity_sub_request",
 *   name = @Translation("Entity sub request"),
 *   description = @Translation("Get data from a sub request to the URL of an entity."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("The data")
 *   ),
 *   consumes = {
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("Root value")
 *     ),
 *     "key" = @ContextDefinition("string",
 *       label = @Translation("The data key")
 *     )
 *   }
 * )
 */
class ThunderEntitySubRequest extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The HTTP kernel service.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The request stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

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
      $container->get('request_stack')
    );
  }

  /**
   * Breadcrumb constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $httpKernel
   *   The HTTP kernel service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   */
  public function __construct(
    array $configuration,
    string $pluginId,
    $pluginDefinition,
    HttpKernelInterface $httpKernel,
    RequestStack $requestStack
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->httpKernel = $httpKernel;
    $this->requestStack = $requestStack;
  }

  /**
   * Resolve data from a sub request.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $key
   *   The key, where the data is stored in the sub request.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   The cacheable dependency interface.
   *
   * @return mixed
   *   The data.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function resolve(EntityInterface $entity, string $key, RefinableCacheableDependencyInterface $metadata) {
    $currentRequest = $this->requestStack->getCurrentRequest();
    $request = Request::create(
      $entity->toUrl()->getInternalPath(),
      'GET',
      [MainContentViewSubscriber::WRAPPER_FORMAT => 'thunder_gqls'],
      $currentRequest->cookies->all(),
      $currentRequest->files->all(),
      $currentRequest->server->all()
    );

    if ($session = $currentRequest->getSession()) {
      $request->setSession($session);
    }

    /** @var \Symfony\Component\HttpFoundation\JsonResponse $response */
    $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);

    $content = (string) $response->getContent();

    return Json::decode($content)[$key];
  }

}
