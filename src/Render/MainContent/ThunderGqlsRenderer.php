<?php

namespace Drupal\thunder_gqls\Render\MainContent;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Thunder GraphQL Schema content renderer.
 *
 * @internal
 */
class ThunderGqlsRenderer implements MainContentRendererInterface {

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
   * Constructs a new JsonRenderer.
   *
   * @param \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface $breadcrumbManager
   *   The breadcrumb manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The route match service.
   */
  public function __construct(BreadcrumbBuilderInterface $breadcrumbManager, CurrentRouteMatch $currentRouteMatch) {
    $this->breadcrumbManager = $breadcrumbManager;
    $this->currentRouteMatch = $currentRouteMatch;
  }

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $json = [];

    $breadCrumb = [];
    foreach ($this->breadcrumbManager->build($this->currentRouteMatch->getCurrentRouteMatch())->getLinks() as $link) {
      $breadCrumb[] = [
        'url' => $link->getUrl()->toString(),
        'title' => $link->getText(),
      ];
    }
    $json['breadcrumb'] = $breadCrumb;

    $response = new CacheableJsonResponse($json, 200);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($main_content));
    return $response;
  }

}
