<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\Schema;

use Drupal\Core\Url;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\Plugin\GraphQL\Schema\ComposableSchema;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\thunder_gqls\Traits\ResolverHelperTrait;

/**
 * Tha base schema for Thunder composable schema.
 *
 * @Schema(
 *   id = "thunder",
 *   name = "Thunder composable schema"
 * )
 */
class ThunderSchema extends ComposableSchema {

  use ResolverHelperTrait;

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
    $this->registry = new ResolverRegistry();
    $this->createResolverBuilder();

    $this->resolveBaseTypes();

    return $this->registry;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSchemaDefinition() {
    return SdlSchemaPluginBase::getSchemaDefinition();
  }

  /**
   * Resolve custom types, that are used in multiple places.
   */
  private function resolveBaseTypes() {
    $this->addFieldResolverIfNotExists('Link', 'url',
      $this->builder->callback(function ($parent) {
        if (!empty($parent) && isset($parent['uri'])) {
          $urlObject = Url::fromUri($parent['uri']);
          $url = $urlObject->toString(TRUE)->getGeneratedUrl();
        }
        return $url ?? '';
      })
    );

    $this->addFieldResolverIfNotExists('Link', 'title',
      $this->builder->callback(function ($parent) {
        return $parent['title'];
      })
    );
  }

}
