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

This will return whatever it finds behind /example-page, and depending on whether it is a user page, a term page or
article node, it will contain the requested fields.

### Query parameter

To simplify request, hard coded strings as the "path" in the previous example can be moved to [query variables](https://graphql.org/learn/queries/#variables).

For this we slightly change the  query to:

```graphql
query($path: String!) {
  page(path: $path) {
    name
    # Add your fields
  }
}
```

Then we add the $path variable with a json string like this:

```json
{
  "path": "/example-page"
}
```

This variable can be added in the GraphQL explorer in the corresponding input field.
All following examples will assume a variable definition like this.

### Paragraphs example

Articles and taxonomy terms contain paragraph fields in Thunder, the following example shows how to request paragraphs'
content.

```graphql
query($path: String!) {
  page(path: $path) {
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
}
```

As you can see, the paragraphs are located in the content field. Different paragraphs have different fields,
so we again use the "... on" syntax to request the correct ones. In the ParagraphPinterest example, the URL
is directly located on the paragraphs' level, and not inside the entity_reference field, where it can be found in the
Drupal schema. This is an example on how we try to simplify and hide Drupal specific implementations.

## Breadcrumb

The Drupal breadcrumb for a given path can be retrieved with this query:

```graphql
query($path: String!) {
  breadcrumb(path: $path) {
    url
    title
  }
}
```

## Combined queries

You can submit multiple queries with one request. EAn example query for both breadcrumb and page for the same path
would be:

```graphql
query($path: String!) {
  breadcrumb(path: $path) {
    url
    title
  }

  page(path: $path) {
    name
    # Add your fields
  }
}
```

## Entity lists

Some fields contain lists of entities, an example are the article lists for taxonomy terms. Those fields have parameters
for offset and limit. The result will contain a list of entities, and the number of total items for that list.
For example the channel page has a list of articles within that channel:

```graphql
query($path: String!) {
  page(path: $path) {
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
}
```

## Menu

Drupal's menus are queried as well. To get the main menu for a given path, you can send this query:

```graphql
query($path: String!) {
  menu(id: "main" path: $path) {
    name
    id
    items {
      title
      url
      inActiveTrail
      children {
        title
        url
        inActiveTrail
      }
    }
  }
}
```

The inActiveTrail field tells you, which menu entry represents the current path.

To retrieve multiple menus with one request, you can use [GraphQL aliases](https://graphql.org/learn/queries/#aliases):

```graphql
query($path: String!) {
  mainMenu: menu(id: "main" path: $path) {
    name
    id
    items {
      title
      url
    }
  }

  footerMenu: menu(id: "footer" path: $path) {
    name
    id
    items {
      title
      url
    }
  }
}
```
