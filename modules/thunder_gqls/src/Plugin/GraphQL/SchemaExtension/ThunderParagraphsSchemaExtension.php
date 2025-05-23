<?php

namespace Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\thunder_gqls\GraphQL\ParagraphsTypeResolver;

/**
 * The paragraph schema extension.
 *
 * @SchemaExtension(
 *   id = "thunder_paragraphs",
 *   name = "Paragraph extension",
 *   description = "Adds paragraphs and their fields (required).",
 *   schema = "thunder"
 * )
 */
class ThunderParagraphsSchemaExtension extends ThunderSchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    parent::registerResolvers($registry);

    $this->registry->addTypeResolver(
      'Paragraph',
      new ParagraphsTypeResolver($registry->getTypeResolver('Paragraph'))
    );

    $this->resolveFields();
  }

  /**
   * Add paragraph field resolvers.
   */
  protected function resolveFields(): void {

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
      $this->builder->fromPath('entity', 'field_media.entity.field_url.value'),
    );
    $this->addFieldResolverIfNotExists('ParagraphTwitter', 'provider',
      $this->builder->fromValue('twitter')
    );

    // Instagram.
    $this->resolveParagraphInterfaceFields('ParagraphInstagram');
    $this->addFieldResolverIfNotExists('ParagraphInstagram', 'url',
      $this->builder->fromPath('entity', 'field_media.entity.field_url.value'),
    );
    $this->addFieldResolverIfNotExists('ParagraphInstagram', 'provider',
      $this->builder->fromValue('instagram')
    );

    // Pinterest.
    $this->resolveParagraphInterfaceFields('ParagraphPinterest');
    $this->addFieldResolverIfNotExists('ParagraphPinterest', 'url',
      $this->builder->fromPath('entity', 'field_media.entity.field_url.value'),
    );
    $this->addFieldResolverIfNotExists('ParagraphPinterest', 'provider',
      $this->builder->fromValue('pinterest')
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
    $this->addFieldResolverIfNotExists('ParagraphVideo', 'metaData',
      $this->builder->fromPath('entity', 'field_video.entity')
    );
    $this->addFieldResolverIfNotExists('ParagraphVideo', 'provider',
      $this->builder->compose(
        $this->builder->fromPath('entity', 'field_video.entity'),
        $this->builder->produce('thunder_media_provider')
          ->map('media', $this->builder->fromParent())
      )
    );
    $this->addFieldResolverIfNotExists('ParagraphVideo', 'url',
      $this->builder->fromPath('entity', 'field_video.entity.field_media_video_embed_field.value'),
    );

    // Quote.
    $this->resolveParagraphInterfaceFields('ParagraphQuote');
    $this->addFieldResolverIfNotExists('ParagraphQuote', 'text',
      $this->builder->fromPath('entity', 'field_text.processed')
    );

  }

}
