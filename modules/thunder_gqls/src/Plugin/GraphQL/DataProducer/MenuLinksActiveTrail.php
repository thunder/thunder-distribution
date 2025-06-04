<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\DataProducer;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
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
 *   produces = @ContextDefinition("map",
 *     label = @Translation("Menu link"),
 *     multiple = TRUE
 *   ),
 *   consumes = {
 *     "menu" = @ContextDefinition("entity:menu",
 *       label = @Translation("Menu")
 *     ),
 *     "url" = @ContextDefinition("any",
 *       label = @Translation("The url"),
 *       required = FALSE
 *     ),
 *   }
 * )
 */
class MenuLinksActiveTrail extends DataProducerPluginBase implements ContainerFactoryPluginInterface {
  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu.link_tree'),
      $container->get('plugin.manager.menu.link')
    );
  }

  /**
   * MenuItems constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuLinkTree
   *   The menu link tree service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   The menu link manager service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected readonly MenuLinkTreeInterface $menuLinkTree, protected readonly MenuLinkManagerInterface $menuLinkManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Resolver.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   The menu interface.
   * @param \Drupal\Core\Url|null $url
   *   The path argument.
   *
   * @return array
   *   The menu links.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function resolve(MenuInterface $menu, ?Url $url): array {
    $parameters = new MenuTreeParameters();

    if ($url && $url->isRouted()) {
      $links = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters(), $menu->id());

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
    }

    $tree = $this->menuLinkTree->load($menu->id(), $parameters);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    return array_filter($this->menuLinkTree->transform($tree, $manipulators), fn(MenuLinkTreeElement $item): bool => $item->link instanceof MenuLinkInterface && $item->link->isEnabled());
  }

}
