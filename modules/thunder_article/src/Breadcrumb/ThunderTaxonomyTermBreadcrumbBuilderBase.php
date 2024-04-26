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
use Drupal\taxonomy\TermInterface;

/**
 * Class to define the menu_link breadcrumb builder.
 */
abstract class ThunderTaxonomyTermBreadcrumbBuilderBase implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * Site configFactory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

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
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityRepositoryInterface $entityRepository, ConfigFactoryInterface $configFactory) {
    $this->entityRepository = $entityRepository;
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->configFactory = $configFactory;
  }

  /**
   * Get the term to produce the breadcrumb for.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Breadcrumb\Breadcrumb $breadcrumb
   *   The breadcrumb.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   The current term.
   */
  abstract protected function getCurrentTerm(RouteMatchInterface $route_match, Breadcrumb $breadcrumb): TermInterface;

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match): Breadcrumb {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['route']);

    $term = $this->getCurrentTerm($route_match, $breadcrumb);

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
    if (!$links || '/' . $links[0]->getUrl()->getInternalPath() != $this->configFactory->get('system.site')->get('page.front')) {
      array_unshift($links, Link::createFromRoute($this->t('Home'), '<front>'));
    }

    return $breadcrumb->setLinks($links);
  }

}
