<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\Breadcrumb\BreadcrumbManager;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets the breadcrumb of the current path.
 *
 * @DataProducer(
 *   id = "thunder_breadcrumb",
 *   name = @Translation("Breadcrumb"),
 *   description = @Translation("Breadcrumb"),
 *   produces = @ContextDefinition("map",
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
   * @var \Drupal\Core\Breadcrumb\BreadcrumbManager
   */
  protected BreadcrumbManager $breadcrumbManager;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $producer = parent::create($container, $configuration, $plugin_id, $plugin_definition);
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
   * Resolve the breadcrumb.
   *
   * @param string $path
   *   The path.
   * @param \Drupal\Core\Cache\CacheableMetadata $cacheableMetadata
   *   Cache metadata for the subrequest.
   * @param \Drupal\graphql\GraphQL\Execution\FieldContext $fieldContext
   *   The field context of the data producer.
   *
   * @return array
   *   The breadcrumb entries.
   */
  protected function resolve(string $path, CacheableMetadata $cacheableMetadata, FieldContext $fieldContext) : array {
    $build = $this->breadcrumbManager->build($this->currentRouteMatch->getCurrentRouteMatch());

    $breadCrumb = [];
    foreach ($build->getLinks() as $link) {
      $text = $link->getText();
      if ($text instanceof TranslatableMarkup) {
        $text = $text->render();
      }
      $breadCrumb[] = [
        'uri' => $link->getUrl()->toUriString(),
        'title' => $text,
      ];
    }

    $fieldContext->addCacheableDependency($build);
    return $breadCrumb;
  }

}
