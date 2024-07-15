<?php

namespace Drupal\thunder_article\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\taxonomy\TermStorageInterface;

/**
 * Class to define the menu_link breadcrumb builder.
 */
class ThunderArticleBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected TermStorageInterface $termStorage;

  /**
   * Constructs the ThunderArticleBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, protected readonly EntityRepositoryInterface $entityRepository, protected readonly ConfigFactoryInterface $configFactory) {
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

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
  public function build(RouteMatchInterface $route_match): Breadcrumb {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);

    // Add all parent forums to breadcrumbs.
    /** @var \Drupal\node\Entity\Node $node */
    $node = $route_match->getParameter('node');
    $breadcrumb->addCacheableDependency($node);

    // Add all parent forums to breadcrumbs.
    /** @var \Drupal\taxonomy\TermInterface|NULL $term */
    $term = $node->field_channel->entity ?? NULL;

    $links = [];
    if ($term) {
      $breadcrumb->addCacheableDependency($term);

      $channels = $this->termStorage->loadAllParents($term->id());
      foreach (array_reverse($channels) as $term) {
        /** @var \Drupal\taxonomy\TermInterface $term */
        $term = $this->entityRepository->getTranslationFromContext($term);
        $breadcrumb->addCacheableDependency($term);
        $links[] = Link::createFromRoute($term->getName(), 'entity.taxonomy_term.canonical', ['taxonomy_term' => $term->id()]);
      }
    }
    if (!$links || '/' . $links[0]->getUrl()->getInternalPath() !== $this->configFactory->get('system.site')->get('page.front')) {
      array_unshift($links, Link::createFromRoute($this->t('Home'), '<front>'));
    }

    return $breadcrumb->setLinks($links);
  }

}
