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
   * ResolverRegistryInterface.
   *
   * @var \Drupal\graphql\GraphQL\ResolverRegistryInterface
   */
  protected $registry;

  /**
   * ResolverBuilder.
   *
   * @var \Drupal\graphql\GraphQL\ResolverBuilder
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry() {
    $this->builder = new ResolverBuilder();
    $this->registry = new ResolverRegistry();

    $this->addInterfaces();
    $this->addQueryFields();
    $this->addArticleFields();
    $this->addChannelFields();
    $this->addTagFields();
    $this->addImageFields();
    $this->addParagraphTextFields();
    $this->addParagraphImageFields();
    $this->addParagraphImageListFields();
    $this->addParagraphEmbedFields();

    return $this->registry;
  }

  /**
   * TODO: this should be much less hard coded and better pluggable.
   *
   */
  protected function addInterfaces () {
    $this->registry->addTypeResolver('ContentType', function ($value) {
      if ($value instanceof ContentEntityInterface) {
        return ucfirst($value->bundle());
      }
      throw new Error('Could not resolve content type.');
    });

    $this->registry->addTypeResolver('ContentElement', function ($value) {
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
   * Function addChannelFields.
   */
  protected function addChannelFields() {
    $this->addContentTypeInterfaceFields('Channel', $this->registry, $this->builder);

    $this->registry->addFieldResolver('Channel', 'content',
      $this->builder->produce('entity_reference_revisions')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_paragraphs'))
    );
  }

  /**
   * Function addTagFields.
   */
  protected function addTagFields() {
    $this->addContentTypeInterfaceFields('Tag', $this->registry, $this->builder);
  }

  /**
   * Function addArticleFields
   */
  protected function addArticleFields() {
    $this->addContentTypeInterfaceFields('Article', $this->registry, $this->builder);

    $this->registry->addFieldResolver('Article', 'seoTitle',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:node'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_seo_title.value'))
    );

    $this->registry->addFieldResolver('Article', 'channel',
      $this->builder->produce('entity_reference')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_channel'))
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
  }

  /**
   * Function addParagraphTextFields.
   */
  protected function addParagraphTextFields() {
    $this->addContentElementInterfaceFields('ParagraphText', $this->registry, $this->builder);

    $this->registry->addFieldResolver('ParagraphText', 'text',
      $this->builder->produce('property_path')
        ->map('type', $this->builder->fromValue('entity:paragraph'))
        ->map('value', $this->builder->fromParent())
        ->map('path', $this->builder->fromValue('field_text.processed'))
    );
  }

  /**
   * Function addParagraphEmbedFields.
   */
  protected function addParagraphEmbedFields() {
    $this->addContentElementInterfaceFields('ParagraphEmbed', $this->registry, $this->builder);

    $this->registry->addFieldResolver('ParagraphEmbed', 'url',
      $this->builder->compose(
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:paragraph'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_media.entity')),
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:media'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_url.value'))
      )
    );
  }

  /**
   * Function addParagraphImageListFields.
   */
  protected function addParagraphImageListFields() {
    $this->addContentElementInterfaceFields('ParagraphImageList', $this->registry, $this->builder);

    $this->registry->addFieldResolver('ParagraphImageList', 'images',
      $this->builder->compose(
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:paragraph'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_media.entity')),
        $this->builder->produce('entity_reference')
          ->map('entity', $this->builder->fromParent())
          ->map('field', $this->builder->fromValue('field_media_images'))
      )
    );
  }

  /**
   * Function addImageFields.
   */
  protected function addImageFields() {
    $this->addContentElementInterfaceFields('Image', $this->registry, $this->builder);

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
  }

  /**
   * Function addParagraphImageFields.
   */
  protected function addParagraphImageFields() {
    $this->addContentElementInterfaceFields('ParagraphImage', $this->registry, $this->builder);

    $this->registry->addFieldResolver('ParagraphImage', 'label',
      $this->builder->compose(
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:paragraph'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_image.entity')),
        $this->builder->produce('entity_label')
          ->map('entity', $this->builder->fromParent())
      )
    );

    $this->registry->addFieldResolver('ParagraphImage', 'copyright',
      $this->builder->compose(
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:paragraph'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_image.entity')),
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:media'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_copyright.value'))
      )
    );

    $this->registry->addFieldResolver('ParagraphImage', 'description',
      $this->builder->compose(
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:paragraph'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_image.entity')),
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:media'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_description.processed'))
      )
    );

    $this->registry->addFieldResolver('ParagraphImage', 'src',
      $this->builder->compose(
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:paragraph'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_image.entity')),
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:media'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_image.entity')),
        $this->builder->produce('image_url')
          ->map('entity', $this->builder->fromParent())
      )
    );

    $this->registry->addFieldResolver('ParagraphImage', 'width',
      $this->builder->compose(
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:paragraph'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_image.entity')),
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:media'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_image.width'))
      )
    );

    $this->registry->addFieldResolver('ParagraphImage', 'height',
      $this->builder->compose(
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:paragraph'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_image.entity')),
        $this->builder->produce('property_path')
          ->map('type', $this->builder->fromValue('entity:media'))
          ->map('value', $this->builder->fromParent())
          ->map('path', $this->builder->fromValue('field_image.height'))
      )
    );
  }

  /**
   * Function addQueryFields.
   */
  protected function addQueryFields() {
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
        ->map('bundles', $this->builder->fromValue(['tag']))
        ->map('id', $this->builder->fromArgument('id'))
    );
  }

}
