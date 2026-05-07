<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\thunder_gqls\GraphQL\PagesTypeResolver;
use Drupal\thunder_gqls\Wrappers\EntityListResponseInterface;

/**
 * Schema extension for page types.
 *
 * @SchemaExtension(
 *   id = "thunder_pages",
 *   name = "Content pages",
 *   description = "Adds page types and their fields (required).",
 *   schema = "thunder"
 * )
 */
class ThunderPagesSchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    parent::registerResolvers($registry);

    $this->registry->addTypeResolver(
      'Page',
      new PagesTypeResolver($registry->getTypeResolver('Page'))
    );

    $this->resolveFields();
  }

  /**
   * Add page field resolvers.
   */
  protected function resolveFields(): void {

    // Page.
    $this->addFieldResolverIfNotExists('Query', 'page',
      $this->fromRoute($this->builder->fromArgument('path'))
    );

    $this->resolvePageInterfaceQueryFields('node', 'node');

    // Teaser.
    $this->addSimpleCallbackFields('Teaser', ['image', 'text']);

    // Article and NewsArticle.
    $articleTypes = ['article' => 'Article', 'newsArticle' => 'NewsArticle'];
    foreach ($articleTypes as $bundle => $type) {
      $this->resolvePageInterfaceFields($type, 'node');
      $this->resolvePageInterfaceQueryFields($bundle, 'node');

      $this->addFieldResolverIfNotExists($type, 'seoTitle',
        $this->builder->fromPath('entity', 'field_seo_title.value')
      );

      $this->addFieldResolverIfNotExists($type, 'channel',
        $this->fromEntityReference('field_channel', NULL, FALSE)
      );

      $this->addFieldResolverIfNotExists($type, 'tags',
        $this->fromEntityReference('field_tags')
      );

      $this->addFieldResolverIfNotExists($type, 'content',
        $this->fromEntityReferenceRevisions('field_paragraphs')
      );

      $this->addFieldResolverIfNotExists($type, 'teaser',
        $this->builder->callback(fn(ContentEntityInterface $entity): array => [
          'image' => $entity->field_teaser_media->entity,
          'text' => $entity->field_teaser_text->value,
        ])
      );
    }

    // Basic page.
    $this->resolvePageInterfaceFields('BasicPage', 'node');

    $this->addFieldResolverIfNotExists('BasicPage', 'content',
      $this->fromEntityReferenceRevisions('field_paragraphs')
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
        ->map('bundles', $this->builder->fromValue(['article', 'news_article']))
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
        ->map('bundles', $this->builder->fromValue(['article', 'news_article']))
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

    $this->registry->addFieldResolver('User', 'mail',
      $this->builder->compose(
        $this->builder->produce('field')
          ->map('entity', $this->builder->fromParent())
          ->map('field', $this->builder->fromValue('mail')),
        $this->builder->fromPath('field:string', '0.value')
      )
    );

    $this->addFieldResolverIfNotExists('User', 'access',
      $this->builder->compose(
        $this->builder->produce('field')
          ->map('entity', $this->builder->fromParent())
          ->map('field', $this->builder->fromValue('access')),
        $this->builder->fromPath('field:string', '0.value')
      )
    );

    $this->addFieldResolverIfNotExists('User', 'published',
      $this->builder->compose(
        $this->builder->produce('field')
          ->map('entity', $this->builder->fromParent())
          ->map('field', $this->builder->fromValue('status')),
        $this->builder->fromPath('field:string', '0.value')
      )
    );

    $this->addFieldResolverIfNotExists('User', 'picture',
      $this->builder->produce('thunder_image')
        ->map('entity', $this->builder->fromPath('entity', 'user_picture.entity'))
        ->map('field', $this->builder->fromPath('entity', 'user_picture'))
    );

    // Entity List.
    $this->addFieldResolverIfNotExists('EntityList', 'total',
      $this->builder->callback(fn(EntityListResponseInterface $entityList): int => $entityList->total())
    );

    $this->addFieldResolverIfNotExists('EntityList', 'items',
      $this->builder->callback(fn(EntityListResponseInterface $entityList) => $entityList->items())
    );
  }

}
