<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\Execution\ResolveContext;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\paragraphs\ParagraphInterface;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * The paragraph schema extension.
 *
 * @SchemaExtension(
 *   id = "thunder_paragraphs",
 *   name = "Paragraph extension",
 *   description = "Adds paragraphs and their fields.",
 *   schema = "thunder"
 * )
 */
class ThunderParagraphsSchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * Add image media field resolvers.
   */
  public function registerResolvers(ResolverRegistryInterface $registry) {
    parent::registerResolvers($registry);

    $this->registry->addTypeResolver('Paragraph',
      \Closure::fromCallable([
        __CLASS__,
        'resolveParagraphTypes',
      ])
    );

    $this->resolveFields();
  }

  /**
   * Add paragraph field resolvers.
   */
  protected function resolveFields() {
    // Text.
    $this->resolveBaseFields('ParagraphText');

    $this->addFieldResolverIfNotExists('ParagraphText', 'text',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:paragraph'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_text.processed'))
    );

    // Image.
    $this->resolveBaseFields('ParagraphImage');

    $this->addFieldResolverIfNotExists('ParagraphImage', 'image',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:paragraph'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_image.entity'))
    );

    // Twitter.
    $this->resolveBaseFields('ParagraphTwitter');
    $embedEntityProducer = $this->referencedEntityProducer('paragraph', 'field_media');

    $this->addFieldResolverIfNotExists('ParagraphTwitter', 'url',
      $this->builder->compose(
        $embedEntityProducer,
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:media'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_url.value'))
      )
    );

    // Instagram.
    $this->resolveBaseFields('ParagraphInstagram');
    $embedEntityProducer = $this->referencedEntityProducer('paragraph', 'field_media');

    $this->addFieldResolverIfNotExists('ParagraphInstagram', 'url',
      $this->builder->compose(
        $embedEntityProducer,
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:media'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_url.value'))
      )
    );

    // Pinterest.
    $this->resolveBaseFields('ParagraphPinterest');
    $embedEntityProducer = $this->referencedEntityProducer('paragraph', 'field_media');

    $this->addFieldResolverIfNotExists('ParagraphPinterest', 'url',
      $this->builder->compose(
        $embedEntityProducer,
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:media'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_url.value'))
      )
    );

    // Gallery.
    $this->resolveBaseFields('ParagraphGallery');
    $mediaEntityProducer = $this->referencedEntityProducer('paragraph', 'field_media');

    $this->addFieldResolverIfNotExists('ParagraphGallery', 'name',
      $this->builder->compose(
        $mediaEntityProducer,
        $this->builder->produce('entity_label')
          ->map('entity', $this->builder->fromParent())
      )
    );

    $this->addFieldResolverIfNotExists('ParagraphGallery', 'images',
      $this->builder->compose(
        $mediaEntityProducer,
        $this->builder->produce('entity_reference')
          ->map('entity', $this->builder->fromParent())
          ->map('field', $this->builder->fromValue('field_media_images'))
      )
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
  protected function resolveParagraphTypes($value, ResolveContext $context, ResolveInfo $info): string {
    if ($value instanceof ParagraphInterface) {
      return 'Paragraph' . $this->mapBundleToSchemaName($value->bundle());
    }
  }

}
