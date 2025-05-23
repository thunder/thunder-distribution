# Integration of VG Wort into Thunder

This module integrates the VG Wort module into Thunder. The main part of the integration is to provide a GraphQL schema
for thunder Articles.

## Installation

Enable the thunder_vgwort and vgwort modules.

## Configuration

The VG Wort module requires to be configured with the Publisher ID (Karteinummer) and the Domain to use for counter ID
images.
When using the GraphQL API you have to enable the "VG Wort Extension" as well as the "Thunder VG Wort extension" in the
GraphQL server settings.

## Usage

The "Thunder VG Wort extension" add a GraphQL field "vgWort" to the Article type. This field contains the counter ID
image URL and the counter ID.

### Example Query

```graphql
query {
  page(path: "/node/1") {
    ... on Article {
      vgWort {
        url
        counterId
      }
    }
  }
}
```

