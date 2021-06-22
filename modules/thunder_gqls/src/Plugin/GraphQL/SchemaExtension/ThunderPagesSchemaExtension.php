<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Entity\ContentEntityInterface;
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

    // Page.
    $this->addFieldResolverIfNotExists('Query', 'page',
      $this->builder->compose(
        $this->builder->produce('route_load')->map('path', $this->builder->fromArgument('path')),
        $this->builder->produce('route_entity')->map('url', $this->builder->fromParent()),
      )
    );

    // Teaser.
    $this->addSimpleCallbackFields('Teaser', ['image', 'text']);

    // Article.
    $this->resolvePageInterfaceFields('Article', 'node');
    $this->resolvePageInterfaceQueryFields('article', 'node');

    $this->addFieldResolverIfNotExists('Article', 'seoTitle',
      $this->builder->fromPath('entity', 'field_seo_title.value')
    );

    $this->addFieldResolverIfNotExists('Article', 'channel',
      $this->builder->fromPath('entity', 'field_channel.entity')
    );

    $this->addFieldResolverIfNotExists('Article', 'tags',
      $this->fromEntityReference('field_tags')
    );

    $this->addFieldResolverIfNotExists('Article', 'content',
      $this->fromEntityReferenceRevisions('field_paragraphs')
    );

    $this->addFieldResolverIfNotExists('Article', 'teaser',
     $this->builder->callback(function (ContentEntityInterface $entity) {
       return [
         'image' => $entity->field_teaser_media->entity,
         'text' => $entity->field_teaser_text->value,
       ];
     })
    );

    // Basic page.
    $this->resolvePageInterfaceFields('BasicPage', 'node');

    $this->addFieldResolverIfNotExists('BasicPage', 'content',
      $this->builder->fromPath('entity', 'body.processed')
    );

    // Tags.
    $this->resolvePageInterfaceFields('Tags', 'taxonomy_term');
    $this->resolvePageInterfaceQueryFields('tags', 'taxonomy_term');

    $this->addFieldResolverIfNotExists('Tags', 'content',
      $this->fromEntityReferenceRevisions('field_paragraphs')
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
    $this->resolvePageInterfaceFields('Channel', 'taxonomy_term');
    $this->resolvePageInterfaceQueryFields('channel', 'taxonomy_term');

    $this->addFieldResolverIfNotExists('Channel', 'content',
      $this->fromEntityReferenceRevisions('field_paragraphs')
    );

    $this->addFieldResolverIfNotExists('Channel', 'parent',
      $this->builder->fromPath('entity', 'parent.entity')
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
    $this->resolvePageInterfaceFields('User', 'user');
    $this->resolvePageInterfaceQueryFields('user', 'user');

    $this->addFieldResolverIfNotExists('User', 'mail',
      $this->builder->fromPath('entity', 'mail.value')
    );

    $this->addFieldResolverIfNotExists('User', 'access',
      $this->builder->fromPath('entity', 'access.value')
    );

    $this->addFieldResolverIfNotExists('User', 'published',
      $this->builder->fromPath('entity', 'status.value')
    );

    $this->addFieldResolverIfNotExists('User', 'picture',
      $this->builder->produce('thunder_image')
        ->map('entity', $this->builder->fromPath('entity', 'user_picture.entity'))
        ->map('field', $this->builder->fromPath('entity', 'user_picture'))
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
   *
   * @throws \Exception
   */
  protected function resolvePageTypes($value, ResolveContext $context, ResolveInfo $info): string {
    if ($value instanceof NodeInterface || $value instanceof TermInterface || $value instanceof UserInterface) {
      if ($value->bundle() === 'page') {
        return 'BasicPage';
      }
      return $this->mapBundleToSchemaName($value->bundle());
    }
    throw new \Exception('Invalid page type.');
  }

}
