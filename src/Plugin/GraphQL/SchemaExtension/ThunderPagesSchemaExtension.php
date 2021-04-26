<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\thunder_gqls\Wrappers\EntityListResponse;
use Drupal\user\UserInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * Schema extension for page types.
 *
 * @SchemaExtension(
 *   id = "thunder_pages",
 *   name = "Content pages",
 *   description = "Adds page types and their fields.",
 *   schema = "thunder"
 * )
 */
class ThunderPagesSchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    parent::registerResolvers($registry);

    $this->registry->addTypeResolver('Page',
      \Closure::fromCallable([
        __CLASS__,
        'resolvePageTypes',
      ])
    );

    $this->resolveFields();
  }

  /**
   * Add article field resolvers.
   */
  protected function resolveFields() {
    // Article.
    $this->resolvePageInterfaceFields('Article');
    $this->resolvePageInterfaceQueryFields('article', 'node');

    $this->addFieldResolverIfNotExists('Article', 'published',
      $this->builder->produce('entity_published')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists('Article', 'author',
      $this->builder->produce('entity_owner')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists('Article', 'seoTitle',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:node'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_seo_title.value'))
    );

    $this->addFieldResolverIfNotExists('Article', 'channel',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:taxonomy_term'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_channel.entity'))
    );

    $this->addFieldResolverIfNotExists('Article', 'tags',
      $this->builder->produce('entity_reference')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_tags'))
    );

    $this->addFieldResolverIfNotExists('Article', 'content',
      $this->builder->produce('entity_reference_revisions')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_paragraphs'))
    );

    // Basic page.
    $this->resolvePageInterfaceFields('BasicPage');

    $this->addFieldResolverIfNotExists('BasicPage', 'published',
      $this->builder->produce('entity_published')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists('BasicPage', 'author',
      $this->builder->produce('entity_owner')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists('BasicPage', 'content',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:node'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('body.processed'))
    );

    // Tags.
    $this->resolvePageInterfaceFields('Tags');
    $this->resolvePageInterfaceQueryFields('tags', 'taxonomy_term');

    $this->addFieldResolverIfNotExists('Tags', 'author',
      $this->builder->produce('entity_owner')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists('Tags', 'published',
      $this->builder->produce('entity_published')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists('Tags', 'content',
      $this->builder->produce('entity_reference_revisions')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_paragraphs'))
    );

    $this->addFieldResolverIfNotExists('Tags', 'articles',
      $this->builder->produce('entities_with_term')
        ->map('term', $this->builder->fromParent())
        ->map('type', $this->builder->fromValue('node'))
        ->map('bundles', $this->builder->fromValue(['article']))
        ->map('field', $this->builder->fromValue('field_tags'))
        ->map('offset', $this->builder->fromArgument('offset'))
        ->map('limit', $this->builder->fromArgument('limit'))
        ->map('languages', $this->builder->fromArgument('languages'))
        ->map('sortBy', $this->builder->fromValue([
          [
            'field' => 'created',
            'direction' => 'DESC',
          ],
        ]))
    );

    // Channel.
    $this->resolvePageInterfaceFields('Channel');
    $this->resolvePageInterfaceQueryFields('channel', 'taxonomy_term');

    $this->addFieldResolverIfNotExists('Channel', 'author',
      $this->builder->produce('entity_owner')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists('Channel', 'published',
      $this->builder->produce('entity_published')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists('Channel', 'content',
      $this->builder->produce('entity_reference_revisions')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_paragraphs'))
    );

    $this->addFieldResolverIfNotExists('Channel', 'parent',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:taxonomy_term'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('parent.entity'))
    );

    $this->addFieldResolverIfNotExists('Channel', 'articles',
      $this->builder->produce('entities_with_term')
        ->map('term', $this->builder->fromParent())
        ->map('type', $this->builder->fromValue('node'))
        ->map('bundles', $this->builder->fromValue(['article']))
        ->map('field', $this->builder->fromValue('field_channel'))
        ->map('offset', $this->builder->fromArgument('offset'))
        ->map('limit', $this->builder->fromArgument('limit'))
        ->map('languages', $this->builder->fromArgument('languages'))
        ->map('sortBy', $this->builder->fromValue([
          [
            'field' => 'created',
            'direction' => 'DESC',
          ],
        ]))
        ->map('depth', $this->builder->fromValue(1))
    );

    // User.
    $this->resolvePageInterfaceFields('User');
    $this->resolvePageInterfaceQueryFields('user', 'node');

    $this->addFieldResolverIfNotExists('User', 'mail',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('mail.value'))
    );

    // Entity List.
    $this->addFieldResolverIfNotExists('EntityList', 'total',
      $this->builder->callback(function (EntityListResponse $entityList) {
        return $entityList->total();
      })
    );

    $this->addFieldResolverIfNotExists('EntityList', 'items',
      $this->builder->callback(function (EntityListResponse $entityList) {
        return $entityList->items();
      })
    );
  }

  /**
   * Resolves page types.
   *
   * @param mixed $value
   *   The current value.
   * @param \Drupal\graphql\GraphQL\Execution\ResolveContext $context
   *   The resolve context.
   * @param \GraphQL\Type\Definition\ResolveInfo $info
   *   The resolve information.
   *
   * @return string
   *   Response type.
   */
  protected function resolvePageTypes($value, ResolveContext $context, ResolveInfo $info): string {
    if ($value instanceof NodeInterface || $value instanceof TermInterface || $value instanceof UserInterface) {
      if ($value->bundle() === 'page') {
        return 'BasicPage';
      }
      return $this->mapBundleToSchemaName($value->bundle());
    }
  }

}
