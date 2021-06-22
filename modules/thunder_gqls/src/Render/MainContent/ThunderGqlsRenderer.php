<?php

namespace Drupal\thunder_gqls\Render\MainContent;

use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\MainContent\MainContentRendererInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\metatag\MetatagManager;
use Drupal\schema_metatag\SchemaMetatagManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Thunder GraphQL Schema content renderer.
 *
 * @internal
 */
class ThunderGqlsRenderer implements MainContentRendererInterface {

  /**
   * The rendering service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * The metatag manager service.
   *
   * @var \Drupal\metatag\MetatagManager
   */
  protected $metatagManager;

  /**
   * Constructs a new JsonRenderer.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface $breadcrumbManager
   *   The breadcrumb manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   *   The route match service.
   * @param \Drupal\metatag\MetatagManager $metatagManager
   *   The metatag manager service.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, RendererInterface $renderer, BreadcrumbBuilderInterface $breadcrumbManager, CurrentRouteMatch $currentRouteMatch, MetatagManager $metatagManager) {
    $this->renderer = $renderer;
    $this->metatagManager = $metatagManager;
    $this->breadcrumbManager = $breadcrumbManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $json = [];

    $json['breadcrumb'] = $this->breadcrumb();
    $json['jsonld'] = $this->jsonld();

    $response = new CacheableJsonResponse($json, 200);
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray($main_content));
    return $response;
  }

  /**
   * Generate the breadcrumb for current route.
   *
   * @return array
   *   The breadcrumb.
   */
  protected function breadcrumb(): array {
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

  /**
   * Generate the jsonld string for current route.
   *
   * @return string
   *   The jsonld string.
   */
  protected function jsonld(): string {
    // If nothing was passed in, assume the current entity.
    // @see schema_metatag_entity_load() to understand why this works.
    if (!$this->moduleHandler->moduleExists('schema_metatag')) {
      return '';
    }

    $entity = metatag_get_route_entity();

    if (!($entity instanceof ContentEntityInterface)) {
      return '';
    }

    // Get all the metatags for this entity.
    $metatags = [];
    foreach ($this->metatagManager->tagsFromEntityWithDefaults($entity) as $tag => $data) {
      $metatags[$tag] = $data;
    }

    // Trigger hook_metatags_alter().
    // Allow modules to override tags or the entity used for token replacements.
    $context = ['entity' => $entity];
    $this->moduleHandler->alter('metatags', $metatags, $context);
    $elements = $this->metatagManager->generateElements($metatags, $entity);

    $context = new RenderContext();
    $jsonldString = $this->renderer->executeInRenderContext($context, function () use ($elements) {
      // Parse the Schema.org metatags out of the array.
      if ($items = SchemaMetatagManager::parseJsonld(
        $elements['#attached']['html_head']
      )) {

        // Encode the Schema.org metatags as JSON LD.
        if ($jsonld = SchemaMetatagManager::encodeJsonld($items)) {
          // Pass back the rendered result.
          $html = SchemaMetatagManager::renderArrayJsonLd($jsonld);
          return $this->renderer->render($html);
        }
      }
    });

    return $jsonldString ?? '';
  }

}
