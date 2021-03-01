<?php

namespace Drupal\thunder_schema\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @SchemaExtension(
 *   id = "thunder_content_types",
 *   name = "Content types",
 *   description = "Adds content types and their fields.",
 *   schema = "thunder"
 * )
 */
class ThunderContentTypesSchemaExtension extends ThunderSchemaExtensionPluginBase {

  public function registerResolvers(ResolverRegistryInterface $registry) {
    parent::registerResolvers($registry);

    $this->registry->addTypeResolver('ContentType',
      \Closure::fromCallable([
        __CLASS__,
        'resolveContentTypes',
      ])
    );


    $this->resolveQueryFields();
    $this->resolveFields();
  }

  /**
   * Add article field resolvers.
   */
  protected function resolveFields() {
    /**
     * Article
     */
    $this->resolveContentTypeInterfaceFields('Article');

    $this->registry->addFieldResolver('Article', 'published',
      $this->builder->produce('entity_published')
        ->map('entity', $this->builder->fromParent())
    );

    $this->registry->addFieldResolver('Article', 'author',
      $this->builder->produce('entity_owner')
        ->map('entity', $this->builder->fromParent())
    );

    $this->registry->addFieldResolver('Article', 'seoTitle',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:node'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_seo_title.value'))
    );

    $this->registry->addFieldResolver('Article', 'channel',
      $this->builder->compose(
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:taxonomy_term'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_channel.entity'))
      )
    );

    $this->registry->addFieldResolver('Article', 'tags',
      $this->builder->produce('entity_reference')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_tags'))
    );

    $this->registry->addFieldResolver('Article', 'content',
      $this->builder->produce('entity_reference_revisions')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_paragraphs'))
    );

    /**
     * Tags
     */
    $this->resolveContentTypeInterfaceFields('Tag');

    $this->registry->addFieldResolver('Tag', 'author',
      $this->builder->produce('entity_owner')
        ->map('entity', $this->builder->fromParent())
    );

    $this->registry->addFieldResolver('Tag', 'published',
      $this->builder->produce('entity_published')
        ->map('entity', $this->builder->fromParent())
    );

    $this->registry->addFieldResolver('Tag', 'content',
      $this->builder->produce('entity_reference_revisions')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_paragraphs'))
    );

    /**
     * Channel
     */
    $this->resolveContentTypeInterfaceFields('Channel');

    $this->registry->addFieldResolver('Channel', 'author',
      $this->builder->produce('entity_owner')
        ->map('entity', $this->builder->fromParent())
    );

    $this->registry->addFieldResolver('Channel', 'published',
      $this->builder->produce('entity_published')
        ->map('entity', $this->builder->fromParent())
    );

    $this->registry->addFieldResolver('Channel', 'content',
      $this->builder->produce('entity_reference_revisions')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_paragraphs'))
    );

    /**
     * User
     */
    $this->resolveContentTypeInterfaceFields('User');

    $this->registry->addFieldResolver('User', 'mail',
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
    $this->registry->addFieldResolver('Query', 'article',
      $this->builder->produce('entity_load')
        ->map('type', $this->builder->fromValue('node'))
        ->map('bundles', $this->builder->fromValue(['article']))
        ->map('id', $this->builder->fromArgument('id'))
    );

    $this->registry->addFieldResolver('Query', 'channel',
      $this->builder->produce('entity_load')
        ->map('type', $this->builder->fromValue('taxonomy_term'))
        ->map('bundles', $this->builder->fromValue(['channel']))
        ->map('id', $this->builder->fromArgument('id'))
    );

    $this->registry->addFieldResolver('Query', 'tag',
      $this->builder->produce('entity_load')
        ->map('type', $this->builder->fromValue('taxonomy_term'))
        ->map('bundles', $this->builder->fromValue(['tags']))
        ->map('id', $this->builder->fromArgument('id'))
    );

    $this->registry->addFieldResolver('Query', 'user',
      $this->builder->produce('entity_load')
        ->map('type', $this->builder->fromValue('user'))
        ->map('id', $this->builder->fromArgument('id'))
    );

  }

  /**
   * Resolves content types.
   *
   * @param mixed $value
   * @param \Drupal\graphql\GraphQL\Execution\ResolveContext $context
   * @param \GraphQL\Type\Definition\ResolveInfo $info
   *
   * @return string
   *   Response type.
   */
  protected function resolveContentTypes($value, ResolveContext $context, ResolveInfo $info): string {
    if ($value instanceof NodeInterface || $value instanceof TermInterface || $value instanceof UserInterface) {
      return $this->mapBundleToSchemaName($value->bundle());
    }
  }

}
