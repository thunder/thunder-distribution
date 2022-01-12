<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\system\MenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Return the menu links of a menu with active trail.
 *
 * @DataProducer(
 *   id = "menu_links_active_trail",
 *   name = @Translation("Menu links"),
 *   description = @Translation("Returns the menu links of a menu with active trail."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Menu link"),
 *     multiple = TRUE
 *   ),
 *   consumes = {
 *     "menu" = @ContextDefinition("entity:menu",
 *       label = @Translation("Menu")
 *     ),
 *     "entity" = @ContextDefinition("entity",
 *       label = @Translation("The entity")
 *     ),
 *   }
 * )
 */
class MenuLinksActiveTrail extends DataProducerPluginBase implements ContainerFactoryPluginInterface {
  use DependencySerializationTrait;

  /**
   * The menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

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
      $container->get('menu.link_tree'),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * MenuItems constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuLinkTree
   *   The menu link tree service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   The menu link manager service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, MenuLinkTreeInterface $menuLinkTree, MenuLinkManagerInterface $menuLinkManager) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->menuLinkTree = $menuLinkTree;
    $this->menuLinkManager = $menuLinkManager;
  }

  /**
   * Resolver.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   The menu interface.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity interface.
   *
   * @return array
   *   The menu links.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function resolve(MenuInterface $menu, ContentEntityInterface $entity) {
    $parameters = new MenuTreeParameters();
    $links = $this->menuLinkManager->loadLinksByRoute($entity->toUrl()->getRouteName(), $entity->toUrl()->getRouteParameters(), $menu->id());

    $activeLink = reset($links);
    if ($activeLink) {
      $activeTrail = ['' => ''];

      if ($parents = $this->menuLinkManager->getParentIds(
        $activeLink->getPluginId()
      )) {
        $activeTrail = $parents + $activeTrail;
      }

      $parameters->setActiveTrail($activeTrail);
    }
    $tree = $this->menuLinkTree->load($menu->id(), $parameters);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    return array_filter($this->menuLinkTree->transform($tree, $manipulators), function (MenuLinkTreeElement $item) {
      return $item->link instanceof MenuLinkInterface && $item->link->isEnabled();
    });
  }

}
