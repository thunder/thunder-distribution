<?php

namespace Drupal\thunder_article\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\thunder\Breadcrumb\ThunderTaxonomyTermBreadcrumbBuilderBase;

/**
 * Class to define the menu_link breadcrumb builder.
 */
class ThunderArticleBreadcrumbBuilder extends ThunderTaxonomyTermBreadcrumbBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match): bool {
    // This breadcrumb apply only for all articles.
    $parameters = $route_match->getParameters()->all();
    if (($route_match->getRouteName() === 'entity.node.canonical') && is_object($parameters['node'])) {
      return $parameters['node']->getType() === 'article';
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCurrentTerm(RouteMatchInterface $route_match, Breadcrumb $breadcrumb): ?TermInterface {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $route_match->getParameter('node');
    $breadcrumb->addCacheableDependency($node);

    return $node->field_channel->entity ?? NULL;
  }

}
