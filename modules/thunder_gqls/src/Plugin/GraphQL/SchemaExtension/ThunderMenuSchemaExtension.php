<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;

/**
 * The menu schema extension.
 *
 * @SchemaExtension(
 *   id = "thunder_menu",
 *   name = "Menu types",
 *   description = "Menu type definitions.",
 *   schema = "thunder"
 * )
 */
class ThunderMenuSchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    parent::registerResolvers($registry);

    $this->addFieldResolverIfNotExists('Query', 'menu', $this->builder->compose(
      $this->builder->context('route_path_argument', $this->builder->fromArgument('path')),
      $this->builder->produce('entity_load')
        ->map('type', $this->builder->fromValue('menu'))
        ->map('id', $this->builder->fromArgument('id'))
        ->map('access', $this->builder->fromValue(FALSE))
    ));

    // Menu id.
    $this->addFieldResolverIfNotExists('Menu', 'id',
      $this->builder->fromPath('entity:menu', 'id')
    );

    // Menu name.
    $this->addFieldResolverIfNotExists('Menu', 'name',
      $this->builder->fromPath('entity:menu', 'label')
    );

    // Menu items.
    $this->addFieldResolverIfNotExists(
      'Menu',
      'items',
      $this->builder->produce('menu_links_active_trail')
        ->map('menu', $this->builder->fromParent())
        ->map('url', $this->builder->produce('route_load')
          ->map('path', $this->builder->fromContext('route_path_argument'))
        )

    );

    // Menu item title.
    $this->addFieldResolverIfNotExists(
      'MenuItem',
      'title',
      $this->builder->produce('menu_link_label')
        ->map(
          'link',
          $this->builder->produce('menu_tree_link')
            ->map('element', $this->builder->fromParent())
        )
    );

    // Menu item description.
    $this->addFieldResolverIfNotExists(
      'MenuItem',
      'description',
      $this->builder->produce('menu_link_description')
        ->map(
          'link',
          $this->builder->produce('menu_tree_link')
            ->map('element', $this->builder->fromParent())
        )
    );

    // Menu item expanded.
    $this->addFieldResolverIfNotExists(
      'MenuItem',
      'expanded',
      $this->builder->produce('menu_link_expanded')
        ->map(
          'link',
          $this->builder->produce('menu_tree_link')
            ->map('element', $this->builder->fromParent())
        )
    );

    // Menu item in active trail.
    $this->addFieldResolverIfNotExists(
      'MenuItem',
      'inActiveTrail',
      $this->builder->produce('menu_tree_in_active_trail')
        ->map('element', $this->builder->fromParent())
    );

    // Menu item children.
    $this->addFieldResolverIfNotExists(
      'MenuItem',
      'children',
      $this->builder->produce('menu_tree_subtree')
        ->map('element', $this->builder->fromParent())
    );

    // Menu item url.
    $this->addFieldResolverIfNotExists(
      'MenuItem',
      'url',
      $this->builder->compose(
        $this->builder->produce('menu_link_url')
          ->map('link', $this->builder->produce('menu_tree_link')
            ->map('element', $this->builder->fromParent())
        ),
        $this->builder->produce('url_path')
          ->map('url', $this->builder->fromParent())
      )
    );

  }

}
