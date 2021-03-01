<?php

namespace Drupal\thunder_schema\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\media\MediaInterface;
use Drupal\paragraphs\ParagraphInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @SchemaExtension(
 *   id = "thunder_media",
 *   name = "Media extension",
 *   description = "Adds media entities and their fields.",
 *   schema = "thunder"
 * )
 */
class ThunderMediaSchemaExtension extends ThunderSchemaExtensionPluginBase {

  public function registerResolvers(ResolverRegistryInterface $registry) {
    parent::registerResolvers($registry);

    $this->registry->addTypeResolver('Media',
      \Closure::fromCallable([
        __CLASS__,
        'resolveMediaTypes',
      ])
    );

    $this->resolveFields();
  }

  /**
   * Add image media field resolvers.
   */
  protected function resolveFields() {

    // Image
    $this->resolveBaseFields('Image');
    $this->registry->addFieldResolver('Image', 'copyright',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:media'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_copyright.value'))
    );

    $this->registry->addFieldResolver('Image', 'description',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:media'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_description.processed'))
    );

    $this->registry->addFieldResolver('Image', 'src',
      $this->builder->compose(
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:media'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_image.entity')),
        $this->builder->produce('image_url')
          ->map('entity', $this->builder->fromParent())
      )
    );

    $this->registry->addFieldResolver('Image', 'width',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:media'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_image.width'))
    );

    $this->registry->addFieldResolver('Image', 'height',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:media'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_image.height'))
    );

    $this->registry->addFieldResolver('Image', 'title',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:media'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_image.title'))
    );

    $this->registry->addFieldResolver('Image', 'alt',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:media'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_image.alt'))
    );

    $this->registry->addFieldResolver('Image', 'tags',
      $this->builder->produce('entity_reference')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_tags'))
    );

    // Embed
    $this->resolveBaseFields('Embed');
    $this->registry->addFieldResolver('Embed', 'url',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:media'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_url.value'))
    );
  }


  /**
   * Resolves media types.
   *
   * @param mixed $value
   * @param \Drupal\graphql\GraphQL\Execution\ResolveContext $context
   * @param \GraphQL\Type\Definition\ResolveInfo $info
   *
   * @return string
   *   Response type.
   */
  protected function resolveMediaTypes($value, ResolveContext $context, ResolveInfo $info): string {
    if ($value instanceof MediaInterface) {
      return $this->mapBundleToSchemaName($value->bundle());
    }
  }

}
