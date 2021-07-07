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
  ```graphql
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
  ```

This will return whatever it finds behind /example-page, and depending on whether it is a user page, a term (channel)
or article node is, it will contain the requested fields.

### Paragraphs example

Articles and taxonomy terms contain paragraph fields in Thunder, the following example shows how to request paragraphs'
content.
  ```graphql
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
  ```

As you can see, the paragraphs are located in the content field. Different paragraphs have different fields,
so we again use the "... on" syntax to request the correct ones. In the ParagraphPinterest example, the URL
is directly located on the paragraphs' level, and not inside the entity_reference field, where it can be found in the
Drupal schema. This is an example on how we try to simplify and hide Drupal specific implementations.

## Entity lists

Some fields contain lists of entities, an example are the article lists for taxonomy terms. Those fields have parameters
for offset and limit. The result will contain a list of entities, and the number of total items for that list.
For example the channel page has a list of articles within that channel:
  ```graphql
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
  ```
