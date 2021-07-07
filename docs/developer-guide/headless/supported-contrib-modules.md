# Supported contrib modules

## Access unpublished

With the access unpublished module, you can hand out links to unpublished nodes to a person, that would usually not
have the permission to view unpublished articles.

The module creates a temporary link with an arbitrary hash token. This hash has to be added to the query in the
following way:

```graphql
query($path: String! $auHash: String) {
  accessUnpublishedToken(auHash: $auHash)
  page(path: $path) {
    name
    # Add your fields
  }
}
```

The accessUnpublishedToken request has to be in the first line of the request.

## Metatag and Schema Metatag

Data provided by the metatag and schema metatag (jsonld) modules is exposed by two similar calls and can be added to the page
call in the following way:

```graphql
query($path: String!) {
  metatags(path: $path) {
    tag
    attributes
  }
  jsonld(path: $path)
  page(path: $path) {
    name
    # Add your fields
  }
}
```

The metatag query will return tag name, and the attributes as a json string. The jsonld query will return the jsonld string.
