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
   * {@inheritdoc}
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
    $this->resolveParagraphInterfaceFields('ParagraphText');
    $this->addFieldResolverIfNotExists('ParagraphText', 'text',
      $this->builder->fromPath('entity', 'field_text.processed')
    );

    // Image.
    $this->resolveParagraphInterfaceFields('ParagraphImage');
    $this->addFieldResolverIfNotExists('ParagraphImage', 'image',
      $this->builder->fromPath('entity', 'field_image.entity')
    );

    // Twitter.
    $this->resolveParagraphInterfaceFields('ParagraphTwitter');
    $this->addFieldResolverIfNotExists('ParagraphTwitter', 'url',
      $this->builder->compose(
        $this->builder->fromPath('entity', 'field_media.entity'),
        $this->builder->fromPath('entity', 'field_url.value')
      )
    );

    // Instagram.
    $this->resolveParagraphInterfaceFields('ParagraphInstagram');
    $this->addFieldResolverIfNotExists('ParagraphInstagram', 'url',
      $this->builder->compose(
        $this->builder->fromPath('entity', 'field_media.entity'),
        $this->builder->fromPath('entity', 'field_url.value')
      )
    );

    // Pinterest.
    $this->resolveParagraphInterfaceFields('ParagraphPinterest');
    $this->addFieldResolverIfNotExists('ParagraphPinterest', 'url',
      $this->builder->compose(
        $this->builder->fromPath('entity', 'field_media.entity'),
        $this->builder->fromPath('entity', 'field_url.value')
      )
    );

    // Gallery.
    $this->resolveParagraphInterfaceFields('ParagraphGallery');
    $this->addFieldResolverIfNotExists('ParagraphGallery', 'name',
      $this->builder->compose(
        $this->builder->fromPath('entity', 'field_media.entity'),
        $this->builder->produce('entity_label')
          ->map('entity', $this->builder->fromParent())
      )
    );

    $this->addFieldResolverIfNotExists('ParagraphGallery', 'images',
      $this->builder->compose(
        $this->builder->fromPath('entity', 'field_media.entity'),
        $this->fromEntityReference('field_media_images')
      )
    );

    // Link.
    $this->resolveParagraphInterfaceFields('ParagraphLink');
    $this->addFieldResolverIfNotExists('ParagraphLink', 'links',
      $this->builder->fromPath('entity', 'field_link')
    );

    // Video.
    $this->resolveParagraphInterfaceFields('ParagraphVideo');
    $this->addFieldResolverIfNotExists('ParagraphVideo', 'video',
      $this->builder->fromPath('entity', 'field_video.entity')
    );

    // Quote.
    $this->resolveParagraphInterfaceFields('ParagraphQuote');
    $this->addFieldResolverIfNotExists('ParagraphQuote', 'text',
      $this->builder->fromPath('entity', 'field_text.processed')
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
  protected function resolveParagraphTypes($value, ResolveContext $context, ResolveInfo $info): string {
    if ($value instanceof ParagraphInterface) {
      return 'Paragraph' . $this->mapBundleToSchemaName($value->bundle());
    }
    throw new \Exception('Invalid paragraph type.');
  }

}
