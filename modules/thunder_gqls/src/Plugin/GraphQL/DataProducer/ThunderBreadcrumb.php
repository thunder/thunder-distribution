<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\Core\Breadcrumb\BreadcrumbManager;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Gets the breadcrumb of the current path.
 *
 * @DataProducer(
 *   id = "thunder_breadcrumb",
 *   name = @Translation("Breadcrumb"),
 *   description = @Translation("Breadcrumb"),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Breadcrumb")
 *   ),
 *   consumes = {
 *     "path" = @ContextDefinition("string",
 *       label = @Translation("Path"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class ThunderBreadcrumb extends ThunderEntitySubRequestBase {

  /**
   * The breadcrumb manager.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
   */
  protected $breadcrumbManager;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    $producer = parent::create($container, $configuration, $pluginId, $pluginDefinition);
    $producer->setCurrentRouteMatch($container->get('current_route_match'));
    $producer->setBreadcrumbManager($container->get('breadcrumb'));
    return $producer;
  }

  /**
   * Sets the current route match.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The current route match.
   */
  protected function setCurrentRouteMatch(CurrentRouteMatch $currentRouteMatch): void {
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * Sets the breadcrumb manager service.
   *
   * @param \Drupal\Core\Breadcrumb\BreadcrumbManager $breadcrumbManager
   *   The breadcrumb manager service.
   */
  protected function setBreadcrumbManager(BreadcrumbManager $breadcrumbManager): void {
    $this->breadcrumbManager = $breadcrumbManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function doResolve(CacheableMetadata $cacheableMetadata, FieldContext $fieldContext) {
    $breadCrumb = [];
    foreach ($this->breadcrumbManager->build(
      $this->currentRouteMatch->getCurrentRouteMatch()
    )->getLinks() as $link) {
      $breadCrumb[] = [
        'uri' => $link->getUrl()->toUriString(),
        'title' => $link->getText(),
      ];
    }
    return $breadCrumb;
  }

}
