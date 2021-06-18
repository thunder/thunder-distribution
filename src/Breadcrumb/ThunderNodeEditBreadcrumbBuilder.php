<?php

namespace Drupal\thunder\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class to define the breadcrumb builder.
 */
class ThunderNodeEditBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return in_array($route_match->getRouteName(), [
      'entity.node.edit_form',
      'node.add',
      'node.add_page',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);

    $links[] = Link::createFromRoute($this->t('Home'), '<front>');
    $links[] = Link::createFromRoute($this->t('Overview'), 'system.admin_content');

    if ($route_match->getRouteName() == 'node.add') {
      $links[] = Link::createFromRoute($this->t('Add content'), 'node.add_page');
    }

    return $breadcrumb->setLinks($links);
  }

}
