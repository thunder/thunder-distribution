# Introduction

The thunder_gqls module provides a GraphQL API schema and implementation for Thunder based on the Drupal GraphQL module
version 4.
Version 4 of the GraphQL module does not provide an out-of-the-box API for Drupal, as previous versions did. Instead, it
provides the ability to define a schema independent of the underlying Drupal installation, and utilities to map fields
defined in that schema to data from Drupal.

To get most of this documentation, you should have a basic understanding of what GraphQL is, and how to do requests
against GraphQL endpoints. A good starting point for this is the official
[GraphQl documentation](https://graphql.org/learn/).

## Motivation to go with GraphQL 4

Drupal core provides already a turn-key implementation for JSON-API, which basically just needs to be enabled and
configured, and it is good to go. Similarly, version 3 of the GraphQL module is as quickly usable. Both modules expose
all data structures from Drupal as they are.

So why did we manually implement an API? While it is very convenient to have schemas automatically created, it also
leads to an API that is very close to the structure of Drupal. A consumer would have to know the relationships of
entities within Drupal. Especially when working with paragraphs and media entities, you would have to be aware of the
entity references to get to the actual data.
For example, we use media entities for images in paragraphs. The referencing goes unconventionally deep in this case:
If you wanted to get the src attribute of an image in such a paragraph, you would have to dereference
Article => Paragraph => Media Entity => File Entity (src).

Another pain point is, that field names are automatically created. This leads to two separate problems: First, field
names are awkward and again very Drupal specific. In GraphQL 3 we have entityUuid instead of uuid and fieldMyField
instead of just myField.
Second, since field names are automatically generated out of the machine name, the API would change, as soon as you change
the machine name. This sounds not very likely, and for actual fields it should not happen, but sometimes even plugin
names are used to create the schema, and plugins could be exchanged (we had an example of a views-plugin, that was exchanged).

Finally, routing with those automated APIs is very often a process that requires two requests, instead of one.
Usually you just have some URL string, that could be a route to a node, a user, a term or any other entity. To get
the actual data, you will have to do a route query first, to get the information on what kind of entity you are looking at
(plus its ID), and then you would have to do a specific node, term or user query to get the actual page.

# Basic Ideas

We introduce three main interfaces for which interfaces covering all main data types used in Thunder.

1) Page
2) Media
3) Paragraph

The Page interface is for all Drupal entities that have a URL, in Thunder that could be nodes, terms, users and similar.
This gives us the possibility to request a page from a route without knowing if it is an article or a channel for example.

The Media interface is for all media entities, and the Paragraph interface for all paragraph entities.

As described above, we try to minimize references and keep fields as flat as possible - especially if the references are
very Drupal specific. Also, Drupal specific field prefixes should be avoided, they make no sense for the frontend.

One example would be the Image type, which is implementing the Media interface.
In Drupal, media entity fields are distributed between several entities, because the file entity does provide
the basic file information, and the media entity adds more data fields to that, while referencing a file. Directly
translated to a GraphQL API it would look similar to:

    type MediaImage {
      entityLabel
      fieldDescription
      fieldImage {
        src
        alt
        width
        height
      }
    }

When you think about images as a frontend developer, you might expect datastructures similar to the following:

    type MediaImage {
      name
      description
      src
      alt
      width
      height
    }

This is much cleaner and does not expose internal Drupal structures and naming.

# Usage

## Routing
The starting point for most requests will be a URL. Usually, you cannot know what kind of content you will find behind
that URL, meaning, which fields you would be able to request. We have simplified this in the Thunder GraphQL schema by
introducing the page() request, which internally routes the URL to the correct entity and returns the entity or entity
bundle as a Page interface. Multiple page types can then be queried with the "... on Type" construct.

Let's take a look at some examples.

## Pages query

All examples can be tested in the GraphQL explorer (admin/config/graphql/servers/manage/thunder_graphql/explorer).
The explorer will also give you a nice autocomplete, and show you all currently available fields.

### Basic example
First a basic example for a page query. All we know so far is that the path is "/example-page". So, how do we get the
content?

    {
      page(path: "/example-page") {
        name
        ... on User {
          mail
        }
        ... on Channel {
          parent {
            name
          }
        }
        ... on Article {
          seoTitle
        }
    }

This will return whatever it finds behind /example-page, and depending on whether it is a user page, a term (channel)
or article node is, it will contain the requested fields.

### Paragraphs example

Articles and taxonomy terms contain paragraph fields in Thunder, the following example shows how to request paragraphs'
content.

    {
      page(path: "/example-page") {
        name
        ... on Article {
          seoTitle
          content {
            ... on ParagraphPinterest {
              url
            }
            ... on ParagraphText {
              text
            }
          }
        }
    }

As you can see, the paragraphs are located in the content field. Different paragraphs have different fields,
so we again use the "... on" syntax to request the correct ones. In the ParagraphPinterest example, the URL
is directly located on the paragraphs' level, and not inside the entity_reference field, where it can be found in the
Drupal schema. This is an example on how we try to simplify and hide Drupal specific implementations.

## Entity lists

Some fields contain lists of entities, an example are the article lists for taxonomy terms. Those fields have parameters
for offset and limit. The result will contain a list of entities, and the number of total items for that list.
For example the channel page has a list of articles within that channel:

    {
      page(path: "/example-term") {
        name
        ... on Channel {
          articles(offset: 0 limit: 10) {
            total
            items {
              name
              url
            }
          }
        }
    }

# Extending

The graphql module has an extension mechanism, called composable schema, that can be used in your projects to extend the Thunder schema with your
custom types. We added some base classes and helper methods to simplify that work.
The basic idea of the composable schema is described in the [GraphQl Module documentation](https://drupal-graphql.gitbook.io/graphql/v/8.x-4.x/advanced/composable-schemas)
As described in the documentation, you will need three files to extend the schema: Two schema files in the graphql folder
of your module:

- your_schema_name.base.graphqls
- your_schema_name.extension.graphqls

And a PHP class file in src/Plugin/GraphQL/SchemaExtension

- YourSchemaNameSchemaExtension.php

You will find examples for that in the thunder_gqls module, for all the schema extension we provide.

Let's do some examples: We will extend the Thunder schema with our own types. To do so, we first create a new
custom module called myschema:

    drush generate module --answers='{"name": "My Schema", "machine_name": "myschema", "install_file": false, "libraries.yml": false, "permissions.yml": false, "event_subscriber": false, "block_plugin": false, "controller": false, "settings_form": false}'

This will create a barebone module called myschema in the modules folder. To continue working on your extension go ahead
and create a new folder called graphql and put two empty files in it called myschema.base.graphqls and myschema.extension.graphqls in it.
Now create another empty file called MySchemaSchemaExtension.php in the src/Plugin/GraphQL/SchemaExtension folder.

Your modules' file structure should be similar to this now:

    +-- myschema.info.yml
    +-- myschema.module
    +-- graphql
    |   +-- myschema.base.graphqls
    |   +-- myschema.extension.graphqls
    +-- src
        +-- Plugin
            +-- GraphQL
                +-- SchemaExtension
                    +-- MySchemaSchemaExtension.php

The content of MySchemaSchemaExtension.php should be:

    <?php

    namespace Drupal\myschema\Plugin\GraphQL\SchemaExtension;

    use Drupal\graphql\GraphQL\ResolverRegistryInterface;
    use Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension\ThunderSchemaExtensionPluginBase;

    /**
     * My schema extension.
     *
     * @SchemaExtension(
     *   id = "myschema",
     *   name = "My schema extension",
     *   description = "Adds my schema.",
     *   schema = "thunder"
     * )
     */
    class MySchemaExtension extends ThunderSchemaExtensionPluginBase {

    }

When you enable the module, your (currently empty) schema extension will be added to the list of available schema extensions.
You will now be able to find and enable it on the admin page admin/config/graphql/servers/manage/thunder_graphql

## Add new type
A common task will be to add a new data type. To do so, you will have to add a new type definition in myschema.base.graphqls.
Say, you have added a new content type. Your myschema.base.graphqls should look like this now:

    type MyContentType implements Page {
      id: Int!
      uuid: String!
      name: String!
      entity: String!
      url: String!
      created: String!
      changed: String!
      language: String
      metatags: [MetaTag]
      myCustomField: String
    }

This declares the fields, that will be available through the API. Since it is a node content type, it will have a URL
and should implement the Page interface. This makes it possible to be requested with the page() query.

We have implemented an automatic type resolver for Page types, that creates a GraphQL type from bundle names. It
CamelCases the words separated by underscores and then removes the underscore. If you create a node content type - or
taxonomy vocabulary - called my_content_type, we will automatically create the MyContentType GraphQL type for you.

The first 9 fields, from id to metatags, are mandatory fields from the Page interface, they will be taken care of by
calling `resolvePageInterfaceFields()` (see example below). The "mycustomfield" field is a custom
field, which we do not know about, so you would have to implement producers for it by yourself. This is done in
the MySchemaSchemaExtension.php file.

    <?php

    namespace Drupal\myschema\Plugin\GraphQL\SchemaExtension;

    use Drupal\graphql\GraphQL\ResolverRegistryInterface;
    use Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension\ThunderSchemaExtensionPluginBase;

    /**
     * My schema extension.
     *
     * @SchemaExtension(
     *   id = "myschema",
     *   name = "My schema extension",
     *   description = "Adds my schema.",
     *   schema = "thunder"
     * )
     */
    class MySchemaExtension extends ThunderSchemaExtensionPluginBase {
      /**
       * {@inheritdoc}
       */
      public function registerResolvers(ResolverRegistryInterface $registry) {
        // Call the parent resolver first.
        parent::registerResolvers($registry);

        // This adds all the Page interface fields to the resolver,
        $this->resolvePageInterfaceFields('MyContentType');

        // Now we add field resolvers for our new fields. In this case we simply get
        // the value from the field_mycustomfield. parent::registerResolvers($registry)
        // stores $registry into the registry property, which we should use instead
        // of $registry.
        $this->registry->addFieldResolver('MyContentType', 'mycustomfield',
          $this->builder->fromPath('entity', 'field_mycustomfield.value')
        );
      }
    }

That's it, most of it is boilerplate code, just the `$this->registry->addFieldResolver('MyContentType', 'mycustomfield',` part
is necessary for your custom field. To learn more about producers and which are available out of the box, please
read the [Drupal GraphQl module documentation](https://drupal-graphql.gitbook.io/graphql/v/8.x-4.x/data-producers/producers).

Similar extensions can be made for new media types and new paragraph types. The main difference is, that media and paragraph
type names are prefixed with Media and Paragraph. If you have a custom paragraph called my_paragraph, the GraphQL
type name would be ParagraphMyParagraph, and the media my_media would be called MediaMyMedia.

## Extend existing types

Another common task is extending existing content types with new fields. When adding more fields to the article content
type, you will have to add the producers for those fields.

This is very similar to creating a new type, but instead of using the myschema.base.graphqls file to declare your schema,
you have to use the myschema.extension.graphqls file to extend the existing schema.

    extend type Article {
      hero: MediaImage
    }

This will add a new image field to the Article type. Similar to adding a new content type, we need to add the data producer for
that field in our MySchemaSchemaExtension.php:

    <?php

    namespace Drupal\myschema\Plugin\GraphQL\SchemaExtension;

    use Drupal\graphql\GraphQL\ResolverRegistryInterface;
    use Drupal\thunder_gqls\Plugin\GraphQL\SchemaExtension\ThunderSchemaExtensionPluginBase;

    /**
     * My schema extension.
     *
     * @SchemaExtension(
     *   id = "myschema",
     *   name = "My schema extension",
     *   description = "Adds my schema.",
     *   schema = "thunder"
     * )
     */
    class MySchemaExtension extends ThunderSchemaExtensionPluginBase {
      /**
       * {@inheritdoc}
       */
      public function registerResolvers(ResolverRegistryInterface $registry) {
        // Call the parent resolver first.
        parent::registerResolvers($registry);

        // This adds all the Page interface fields to the resolver,
        $this->resolvePageInterfaceFields('MyContentType');

        // Now we add field resolvers for our new fields. In this case we simply get
        // the value from the field_mycustomfield. parent::registerResolvers($registry)
        // stores $registry into the registry property, which we should use instead
        // of $registry.
        $this->registry->addFieldResolver('MyContentType', 'myCustomField',
          $this->builder->fromPath('entity', 'field_mycustomfield.value')
        );

        // Extending the article
        $this->registry->addFieldResolver('Article', 'hero',
          $this->builder->fromPath('entity', 'field_hero.entity')
        );
      }
    }

### Entity lists

We have a base class for entity lists, which can be used to create your own list definitions.

## Change existing definitions

It is also possible to change existing resolvers. Field resolver and type resolver are simply overridable in your
schema extension class.

### Fields

Existing fields, where you would like to change the producer, e.g. to use a different Drupal field, are very easy: Just
make your own definition in the MySchemaSchemaExtension.php. If you would like to change the Drupal field for
the content field from field_paragraph to field_my_paragraph, you change the producer in your registerResolvers()
method to something like this:

    $this->registry->addFieldResolver('Article', 'content',
      $this->builder->produce('entity_reference_revisions')
        ->map('entity', $this->builder->fromParent())
        ->map('field', $this->builder->fromValue('field_my_paragraphs'))
    );

#### Thunder entity list producer and entities with term producer

The thunder_entity_list producer is a highly configurable producer to create lists of entities based on entity field queries.
You can use it as a field producer for you custom fields. It can also be used as a base producer class for more specific
producers. We include the entities_with_term as an example, which adds the ability to define a term depth (similar to
views) in your queries, when you want to have results for terms as well as their child terms, and presets specific
query conditions, which simplifies the usage.

To use the producer for a field, you first have to define that field in your graphqls file. In this example we add a
related articles field to the existing article type, so we have to add it to myschema.extension.graphqls.

    extend type Article {
      hero: MediaImage
      promotedArticles(offset: Int = 0, limit: Int = 50): EntityList
    }

As you can see in the example, it is possible to expose parameters to the GraphQL client. We recommend limiting the
exposed parameters as much as possible, and not give too much control to the consumer, because generating lists can
produce great load on the server, and you might expose data that you did not expect. Offset and limit should be fine.
Any limit that will be set greater than 100 will not be accepted.

Back in the MySchemaSchemaExtension.php we can now use the thunder_entity_list producer to
resolve that field.

    // Example for the thunder_entity_list list producer.
    $this->registry->addFieldResolver('Article', 'promotedArticles',
      $this->builder->produce('thunder_entity_list')
        ->map('type', $this->builder->fromValue('node'))
        ->map('bundles', $this->builder->fromValue(['article']))
        ->map('offset', $this->builder->fromArgument('offset'))
        ->map('limit', $this->builder->fromArgument('limit'))
        ->map('conditions', $this->builder->fromValue([
          [
            'field' => 'promote',
            'value' => 1,
          ],
        ]))
        ->map('sortBy', $this->builder->fromValue([
          [
            'field' => 'created',
            'direction' => 'DESC',
          ],
        ]))
    );

As you can see, you can give either set hard coded values for the producers parameters, or values from query arguments
(offset and limit in this example). When you want to use context dependent parameters to the conditions, you would
have to use either more query arguments (which could be bad), or implement your own data producer based on
ThunderEntityListProducerBase. You can find an example in EntitiesWithTerm.php where we dynamically add term IDs
to the query conditions.

# Integrated contrib modules

## Access unpublished

With the access unpublished module, you can hand out links to unpublished nodes to a person, that would usually not
have the permission to view unpublished articles.

The module creates a temporary link with an arbitrary hash token. This hash has to be added to the query in the
following way:

    {
      accessUnpublishedToken(auHash: "irCtdmllOqyoocxQ9JSUZNm5waEFmX0v4-ueUnUjPZI")
      page(path: "/example-page") {
        name
      }
    }

The accessUnpublishedToken request has to be in the first line of the request.
