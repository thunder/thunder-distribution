# Installation

In modules folder of a Thunder installation:

    git clone git@github.com:thunder/thunder_gqls.git
    drush en thunder_gqls

You can enable the thunder_demo module to have some articles to work with.

+ open admin/config/graphql in browser click "Create Server"
+ choose a label and custom endpoint to your liking.
+ Select "Composable schema" as schema and enable extensions
+ Hit "Save" button

Back on admin/config/graphql choose "Explorer" from the drop down button

If all works, you should be able to test some queries in the Explorer. For the
example query you have to add the query variable `{"path": "/your-path"}`

# Example

    query ($path: String!) {
      metatags(path: $path) {
        tag
        attributes
      }
      mainMenu: menu(id: "main" path: $path) {
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
      footerMenu: menu(id: "footer" path: $path) {
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
      page(path: $path) {
        uuid
        url
        name
        ... on User {
          mail
        }
        ... on Channel {
          articles(offset: 0 limit: 2) {
            total
            items {
              name
              url
            }
          }
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
          teaser {
            image {
              src
              published
              fallbackMedia {
                src
              }
            }
          }
          content {
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
                derivative(style: "medium") {
                  src
                  width
                }
                focalPoint {
                  x
                  y
                }
              }
            }
            ... on ParagraphLink {
              links {
                url
                title
              }
            }
          }
        }
      }
    }
