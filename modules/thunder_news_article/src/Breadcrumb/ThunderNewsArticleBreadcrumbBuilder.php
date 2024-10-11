<?php

namespace Drupal\thunder_news_article\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\thunder_article\Breadcrumb\ThunderTaxonomyTermBreadcrumbBuilderBase;

/**
 * Class to define the menu_link breadcrumb builder.
 */
class ThunderNewsArticleBreadcrumbBuilder extends ThunderTaxonomyTermBreadcrumbBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match): bool {
    // This breadcrumb apply only for all news articles.
    $parameters = $route_match->getParameters()->all();
    return ($route_match->getRouteName() === 'entity.node.canonical') && is_object($parameters['node']) && $parameters['node']->getType() === 'news_article' && !empty($parameters['node']->field_channel->entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getCurrentTerm(RouteMatchInterface $route_match, Breadcrumb $breadcrumb): TermInterface {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $route_match->getParameter('node');
    $breadcrumb->addCacheableDependency($node);

    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $node->field_channel->entity;
    return $term;
  }

}
