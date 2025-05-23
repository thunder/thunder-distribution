<?php

namespace Drupal\Tests\thunder_gqls\Functional;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Test cache invalidation of GraphQL requests.
 *
 * @group Thunder
 */
class CacheInvalidationTest extends ThunderGqlsTestBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = \Drupal::service('config.factory');
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

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

    $this->assertEquals('Drush Site-Install', $response['@graph'][0]['publisher']['name']);
    $this->setSiteName('Drupal Test Installation');

    $response = $this->getJsonLdFromQuery($query, $variables);
    $this->assertEquals('Drupal Test Installation', $response['@graph'][0]['publisher']['name']);
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

    $responseData = $this->getResponseData($query, $variables)['metatags'];

    // Assert that changing the entity invalidates the cache.
    $descriptionData = $this->jsonDecode($responseData[1]['attributes']);
    $this->assertEquals('description', $descriptionData['name'], 'The meta tag for description is not the sixth tag in the response.');
    $this->assertStringStartsWith('The Drupal community is one of the largest open source communities in the world', $descriptionData['content'], 'The meta tag has the wrong content.');

    $node = $this->getNodeByTitle('Come to DrupalCon New Orleans');
    $node->set('field_teaser_text', 'New teaser text');
    $node->save();

    $responseData = $this->getResponseData($query, $variables)['metatags'];
    $descriptionData = $this->jsonDecode($responseData[1]['attributes']);

    $this->assertEquals('description', $descriptionData['name'], 'The meta tag for description is not the sixth tag in the response.');
    $this->assertEquals('New teaser text', $descriptionData['content'], 'The meta tag has the wrong content.');

    // Assert that changing the site config invalidates the cache.
    $descriptionData = $this->jsonDecode($responseData[6]['attributes']);
    $this->assertEquals('og:site_name', $descriptionData['property'], 'The meta tag for og:site_name is not the seventh tag in the response.');
    $this->assertEquals('Drush Site-Install', $descriptionData['content'], 'The meta tag has the wrong content.');

    $this->setSiteName('Drupal Test Installation');

    $responseData = $this->getResponseData($query, $variables)['metatags'];
    $descriptionData = $this->jsonDecode($responseData[6]['attributes']);

    $this->assertEquals('og:site_name', $descriptionData['property'], 'The meta tag for og:site_name is not the seventh tag in the response.');
    $this->assertEquals('Drupal Test Installation', $descriptionData['content'], 'The meta tag has the wrong content.');
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

    $responseData = $this->getResponseData($query, $variables)['page'];
    $this->assertEquals('Burda Launches Open-Source CMS Thunder', $responseData['articles']['items'][0]['name']);

    // Change the title of the node.
    $node = $this->getNodeByTitle('Burda Launches Open-Source CMS Thunder');
    $node->setTitle('Burda Launches Open-Source CMS Thunder 2020');
    $node->save();

    $responseData = $this->getResponseData($query, $variables)['page'];
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

    $responseData = $this->getResponseData($query, $variables)['breadcrumb'];
    $this->assertEquals('Home', $responseData[0]['title']);

    // Change the title of the term, this changes the url, so the breadcrumb
    // for the same path should be gone.
    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => 'News', 'vid' => 'channel']);
    $term = reset($terms);
    $term->setName('New');
    $term->save();

    $responseData = $this->getResponseData($query, $variables)['breadcrumb'];
    $this->assertEmpty($responseData, 'The breadcrumb should be empty, because the url changed.');

    // Change the variables to the new path, the breadcrumb should be there.
    $variables = '{"path": "/new"}';

    $responseData = $this->getResponseData($query, $variables)['breadcrumb'];
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
    $responseData = $this->getResponseData($query, $variables)['jsonld'];

    // Remove surrounding ld+json script tag.
    $responseData = substr($responseData, 35, -10);
    return $this->jsonDecode($responseData);
  }

  /**
   * Set the site name system configuration.
   *
   * @param string $name
   *   The new site name.
   */
  protected function setSiteName(string $name): void {
    $config = $this->configFactory->getEditable('system.site');
    $config->set('name', $name);
    $config->save();
  }

}
