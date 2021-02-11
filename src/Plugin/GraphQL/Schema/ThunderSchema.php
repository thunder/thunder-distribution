<?php

namespace Drupal\thunder_schema\Plugin\GraphQL\Schema;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;
use Drupal\media\MediaInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\thunder_schema\Plugin\GraphQL\Traits\ContentElementInterfaceResolver;
use Drupal\thunder_schema\Plugin\GraphQL\Traits\ContentTypeInterfaceResolver;
use Drupal\thunder_schema\Wrappers\QueryConnection;
use GraphQL\Error\Error;

/**
 * @Schema(
 *   id = "thunder",
 *   name = "Thunder schema"
 * )
 */
class ThunderSchema extends SdlSchemaPluginBase {

  use ContentTypeInterfaceResolver;
  use ContentElementInterfaceResolver;

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
    $builder = new ResolverBuilder();
    $registry = new ResolverRegistry();

    $this->addInterfaces($registry);
    $this->addQueryFields($registry, $builder);
    $this->addArticleFields($registry, $builder);
    $this->addChannelFields($registry, $builder);
    $this->addTagFields($registry, $builder);
    $this->addImageFields($registry, $builder);
    $this->addParagraphTextFields($registry, $builder);
    $this->addParagraphImageFields($registry, $builder);
    $this->addParagraphImageListFields($registry, $builder);
    $this->addParagraphEmbedFields($registry, $builder);

    return $registry;
  }

  /**
   * TODO: this should be much less hard coded and better pluggable.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   */
  protected function addInterfaces (ResolverRegistryInterface $registry) {
    $registry->addTypeResolver('ContentType', function ($value) {
      if ($value instanceof ContentEntityInterface) {
        return ucfirst($value->bundle());
      }
      throw new Error('Could not resolve content type.');
    });

    $registry->addTypeResolver('ContentElement', function ($value) {
      if ($value instanceof ParagraphInterface) {
        $bundle = $value->bundle();
        switch ($bundle) {
          case 'text':
          case 'image':
            return 'Paragraph' . ucfirst($bundle);
          case 'twitter':
          case 'pinterest':
          case 'instagram':
            // TODO: make this more general, instead of using the cases above
            return 'ParagraphEmbed';
          case 'gallery':
            return 'ParagraphImageList';
        }
      }

      if($value instanceof MediaInterface) {
        return ucfirst($value->bundle());
      }

      throw new Error('Could not resolve element type.');
    });

  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addChannelFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $this->addContentTypeInterfaceFields('Channel', $registry, $builder);

    $registry->addFieldResolver('Channel', 'content',
      $builder->produce('entity_reference_revisions')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_paragraphs'))
    );
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addTagFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $this->addContentTypeInterfaceFields('Tag', $registry, $builder);
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addArticleFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $this->addContentTypeInterfaceFields('Article', $registry, $builder);

    $registry->addFieldResolver('Article', 'seoTitle',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_seo_title.value')),
    );

    $registry->addFieldResolver('Article', 'channel',
      $builder->produce('entity_reference')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_channel'))
    );

    $registry->addFieldResolver('Article', 'channel',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:taxonomy_term'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_channel.entity')),
      )
    );


    $registry->addFieldResolver('Article', 'tags',
      $builder->produce('entity_reference')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_tags'))
    );

    $registry->addFieldResolver('Article', 'content',
      $builder->produce('entity_reference_revisions')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_paragraphs'))
    );
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addParagraphTextFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $this->addContentElementInterfaceFields('ParagraphText', $registry, $builder);

    $registry->addFieldResolver('ParagraphText', 'text',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:paragraph'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_text.processed'))
    );
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addParagraphEmbedFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $this->addContentElementInterfaceFields('ParagraphEmbed', $registry, $builder);

    $registry->addFieldResolver('ParagraphEmbed', 'url',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:paragraph'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_media.entity')),
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:media'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_url.value')),
      )
    );
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addParagraphImageListFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $this->addContentElementInterfaceFields('ParagraphImageList', $registry, $builder);

    $registry->addFieldResolver('ParagraphImageList', 'images',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:paragraph'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_media.entity')),
        $builder->produce('entity_reference')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_media_images')),
      )
    );
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addImageFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $this->addContentElementInterfaceFields('Image', $registry, $builder);

    $registry->addFieldResolver('Image', 'copyright',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:media'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_copyright.value'))
    );

    $registry->addFieldResolver('Image', 'description',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:media'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_description.processed')),
    );

    $registry->addFieldResolver('Image', 'src',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:media'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_image.entity')),
        $builder->produce('image_url')
          ->map('entity', $builder->fromParent()),
      )
    );

    $registry->addFieldResolver('Image', 'width',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:media'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_image.width')),
    );

    $registry->addFieldResolver('Image', 'height',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:media'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_image.height')),

    );
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addParagraphImageFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $this->addContentElementInterfaceFields('ParagraphImage', $registry, $builder);

    $registry->addFieldResolver('ParagraphImage', 'label',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:paragraph'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_image.entity')),
        $builder->produce('entity_label')
          ->map('entity', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('ParagraphImage', 'copyright',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:paragraph'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_image.entity')),
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:media'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_copyright.value')),
      )
    );

    $registry->addFieldResolver('ParagraphImage', 'description',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:paragraph'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_image.entity')),
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:media'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_description.processed')),
      )
    );

    $registry->addFieldResolver('ParagraphImage', 'src',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:paragraph'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_image.entity')),
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:media'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_image.entity')),
        $builder->produce('image_url')
          ->map('entity', $builder->fromParent()),
      )
    );

    $registry->addFieldResolver('ParagraphImage', 'width',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:paragraph'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_image.entity')),
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:media'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_image.width')),
      )
    );

    $registry->addFieldResolver('ParagraphImage', 'height',
      $builder->compose(
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:paragraph'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_image.entity')),
        $builder->produce('property_path')
          ->map('type', $builder->fromValue('entity:media'))
          ->map('value', $builder->fromParent())
          ->map('path', $builder->fromValue('field_image.height')),
      )
    );
  }

  /**
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   */
  protected function addQueryFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Query', 'article',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('node'))
        ->map('bundles', $builder->fromValue(['article']))
        ->map('id', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Query', 'channel',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('taxonomy_term'))
        ->map('bundles', $builder->fromValue(['channel']))
        ->map('id', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Query', 'tag',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('taxonomy_term'))
        ->map('bundles', $builder->fromValue(['tag']))
        ->map('id', $builder->fromArgument('id'))
    );
  }

}
