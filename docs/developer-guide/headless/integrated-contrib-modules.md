# Integrated contrib modules

## Access unpublished

With the access unpublished module, you can hand out links to unpublished nodes to a person, that would usually not
have the permission to view unpublished articles.

The module creates a temporary link with an arbitrary hash token. This hash has to be added to the query in the
following way:
  ```graphql
    {
      accessUnpublishedToken(auHash: "irCtdmllOqyoocxQ9JSUZNm5waEFmX0v4-ueUnUjPZI")
      page(path: "/example-page") {
        name
      }
    }
  ```

The accessUnpublishedToken request has to be in the first line of the request.
