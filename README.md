# Installation

In modules folder of a Thunder installation:

    git clone git@github.com:thunder/thunder_schema.git
    drush en thunder_schema

Might also want to enable the thunder_demo module to have some articles to work with, if you do not have any.

+ open admin/config/graphql in browser click "Create Server"
+ choose a label and custom endpoint to your liking.
+ Select "Composable schema" as schema and enable all extensions in "Schema configuration"
+ Hit "Save" button

Back on admin/config/graphql choose "Explorer" from the drop down button

If all works, you should be able to test some queries in the Explorer.

# Example

    {
      route(path: "/your-path") {
        uuid
        url
        name
        ... on User {
          mail
        }
        ... on Channel {
          content {
            __typename
          }
        }
        ... on Article {
          id
          url
          seoTitle
          language
          author {
            id
            name
            mail
            __typename
          }
          channel {
            url
            name
          }
          content {
            id
            __typename
            ... on ParagraphPinterest {
              url
            }
            ... on ParagraphText {
              text
            }
            ... on ParagraphGallery {
              images {
                src
              }
            }
            ... on ParagraphImage {
              image {
                src
                width
                title
                alt
                name
                tags {
                  name
                }
              }
            }
          }
        }
      }
    }


