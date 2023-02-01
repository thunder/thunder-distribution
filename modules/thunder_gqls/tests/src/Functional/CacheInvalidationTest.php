<?php

namespace Drupal\Tests\thunder_gqls\Functional;

/**
 * Test cache invalidation of GraphQL requests.
 *
 * @group Thunder
 */
class CacheInvalidationTest extends ThunderGqlsTestBase {

  /**
   * Test the cache invalidation of the JsonLD data producer.
   */
  public function testJsonLdCacheInvalidation(): void {
    $query = 'query ($path: String!) {
      jsonld(path: $path)
    }';

    $variables = '{"path": "/come-drupalcon-new-orleans"}';

    $response = $this->getJsonLdFromQuery($query, $variables);
    $this->assertEquals('Come to DrupalCon New Orleans', $response['@graph'][0]['name']);

    // Change the title of the node.
    $node = $this->getNodeByTitle('Come to DrupalCon New Orleans');
    $node->setTitle('Come to DrupalCon New Orleans 2020');
    $node->save();

    $response = $this->getJsonLdFromQuery($query, $variables);
    $this->assertEquals('Come to DrupalCon New Orleans 2020', $response['@graph'][0]['name']);
  }

  /**
   * Test the cache invalidation of the metatags data producer.
   */
  public function testMetatagsCacheInvalidation(): void {
    $query = 'query ($path: String!) {
      metatags(path: $path) {
        tag
        attributes
      }
    }';

    $variables = '{"path": "/come-drupalcon-new-orleans"}';

    $responseData = $this->getResponseValueForKey('metatags', $query, $variables);
    $descriptionData = json_decode($responseData[6]['attributes'], TRUE, 512, JSON_THROW_ON_ERROR);

    $this->assertEquals('description', $descriptionData['name'], 'The meta tag for description is not the sixth tag in the response.');
    $this->assertStringStartsWith('The Drupal community is one of the largest open source communities in the world', $descriptionData['content'], 'The meta tag has the wrong content.');

    // Change the title of the node.
    $node = $this->getNodeByTitle('Come to DrupalCon New Orleans');
    $node->set('field_teaser_text', 'New teaser text');
    $node->save();

    $responseData = $this->getResponseValueForKey('metatags', $query, $variables);
    $descriptionData = json_decode($responseData[6]['attributes'], TRUE, 512, JSON_THROW_ON_ERROR);

    $this->assertEquals('description', $descriptionData['name'], 'The meta tag for description is not the sixth tag in the response.');
    $this->assertEquals('New teaser text', $descriptionData['content'], 'The meta tag has the wrong content.');
  }

  /**
   * Test the cache invalidation of the entity list data producer.
   */
  public function testEntityListCacheInvalidation(): void {
    $query = 'query ($path: String!) {
      page(path: $path) {
        ... on Channel {
          articles {
            items {
              name
            }
          }
        }
      }
    }';

    $variables = '{"path": "/news"}';

    $responseData = $this->getResponseValueForKey('page', $query, $variables);
    $this->assertEquals('Burda Launches Open-Source CMS Thunder', $responseData['articles']['items'][0]['name']);

    // Change the title of the node.
    $node = $this->getNodeByTitle('Burda Launches Open-Source CMS Thunder');
    $node->setTitle('Burda Launches Open-Source CMS Thunder 2020');
    $node->save();

    $responseData = $this->getResponseValueForKey('page', $query, $variables);
    $this->assertEquals('Burda Launches Open-Source CMS Thunder 2020', $responseData['articles']['items'][0]['name']);
  }

  /**
   * Test the cache invalidation of the breadcrumb data producer.
   */
  public function testBreadcrumbCacheInvalidation(): void {
    $query = 'query ($path: String!) {
      breadcrumb(path: $path) {
        title
      }
    }';

    $variables = '{"path": "/news"}';

    $responseData = $this->getResponseValueForKey('breadcrumb', $query, $variables);
    $this->assertEquals('Home', $responseData[0]['title']);

    // Change the title of the term, this changes the url, so the breadcrumb
    // for the same path should be gone.
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'News', 'vid' => 'channel']);
    $term = reset($terms);
    $term->setName('New');
    $term->save();

    $responseData = $this->getResponseValueForKey('breadcrumb', $query, $variables);
    $this->assertEmpty($responseData, 'The breadcrumb should be empty, because the url changed.');

    // Change the variables to the new path, the breadcrumb should be there.
    $variables = '{"path": "/new"}';

    $responseData = $this->getResponseValueForKey('breadcrumb', $query, $variables);
    $this->assertEquals('Home', $responseData[0]['title']);
  }

  /**
   * Get JsonLD from query as array.
   *
   * @param string $query
   *   The graphql jsonld query.
   * @param string $variables
   *   The variables for the query.
   *
   * @return array
   *   The jsonld as array.
   *
   * @throws \JsonException|\GuzzleHttp\Exception\GuzzleException
   *   If the json is invalid or the request failed.
   */
  protected function getJsonLdFromQuery(string $query, string $variables): array {
    $responseData = $this->getResponseValueForKey('jsonld', $query, $variables);

    // Remove surrounding ld+json script tag.
    $responseData = substr($responseData, 35, -10);
    return json_decode($responseData, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  /**
   * Get value for a specific key from response.
   *
   * @param string $key
   *   The key to get.
   * @param string $query
   *   The graphql jsonld query.
   * @param string $variables
   *   The variables for the query.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException|\JsonException
   *   If the json is invalid or the request failed.
   */
  protected function getResponseValueForKey(string $key, string $query, string $variables) {
    $response = $this->query($query, $variables);
    $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');

    return json_decode(
      $response->getBody(),
      TRUE,
      512,
      JSON_THROW_ON_ERROR
    )['data'][$key];
  }

}
