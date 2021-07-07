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

```graphql
type MediaImage {
  entityLabel: String
  fieldDescription: String
  fieldImage: Image
}
type Image {
  src: String
  alt: String
  width: Int
  height: Int
}
```

When you think about images as a frontend developer, you might expect datastructures similar to the following:
```graphql
type MediaImage {
  name: String
  description: String
  src: String
  alt: String
  width: Int
  height: Int
}
```

This is cleaner and does not expose internal Drupal structures and naming.
