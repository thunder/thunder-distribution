<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
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

    $this->resolveQueryFields();
    $this->resolveFields();
  }

  /**
   * Add article field resolvers.
   */
  protected function resolveFields() {
    // Article.
    $this->resolvePageInterfaceFields('Article');

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
      $this->builder->compose(
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:taxonomy_term'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_channel.entity'))
      )
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

    // Tags.
    $this->resolvePageInterfaceFields('Tag');

    $this->addFieldResolverIfNotExists('Tag', 'author',
      $this->builder->produce('entity_owner')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists('Tag', 'published',
      $this->builder->produce('entity_published')
        ->map('entity', $this->builder->fromParent())
    );

    $this->addFieldResolverIfNotExists('Tag', 'content',
      $this->builder->produce('entity_reference_revisions')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_paragraphs'))
    );

    // Channel.
    $this->resolvePageInterfaceFields('Channel');

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

    // User.
    $this->resolvePageInterfaceFields('User');

    $this->addFieldResolverIfNotExists('User', 'mail',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('mail.value'))
    );
  }

  /**
   * Add content query field resolvers.
   */
  protected function resolveQueryFields() {
    $this->addFieldResolverIfNotExists('Query', 'article',
      $this->builder->produce('entity_load')
        ->map('type', $this->builder->fromValue('node'))
        ->map('bundles', $this->builder->fromValue(['article']))
        ->map('id', $this->builder->fromArgument('id'))
    );

    $this->addFieldResolverIfNotExists('Query', 'channel',
      $this->builder->produce('entity_load')
        ->map('type', $this->builder->fromValue('taxonomy_term'))
        ->map('bundles', $this->builder->fromValue(['channel']))
        ->map('id', $this->builder->fromArgument('id'))
    );

    $this->addFieldResolverIfNotExists('Query', 'tag',
      $this->builder->produce('entity_load')
        ->map('type', $this->builder->fromValue('taxonomy_term'))
        ->map('bundles', $this->builder->fromValue(['tags']))
        ->map('id', $this->builder->fromArgument('id'))
    );

    $this->addFieldResolverIfNotExists('Query', 'user',
      $this->builder->produce('entity_load')
        ->map('type', $this->builder->fromValue('user'))
        ->map('id', $this->builder->fromArgument('id'))
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
      return $this->mapBundleToSchemaName($value->bundle());
    }
  }

}
