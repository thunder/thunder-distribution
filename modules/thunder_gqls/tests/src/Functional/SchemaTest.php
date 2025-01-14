<?php

namespace Drupal\Tests\thunder_gqls\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\access_unpublished\Entity\AccessToken;
use Drupal\media\Entity\MediaType;
use Drupal\node\Entity\Node;

/**
 * Test the schema.
 *
 * @group Thunder
 */
class SchemaTest extends ThunderGqlsTestBase {

  /**
   * Tests the article schema.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testSchema(): void {
    $schemas = [
      'article',
      'paragraphs',
      'entities_with_term',
      'menu',
      'menu404',
      'views_menu',
      'breadcrumb',
      'user',
      'basic_page',
    ];
    foreach ($schemas as $schema) {
      $this->runAndTestQuery($schema);
    }
  }

  /**
   * Tests the article schema.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testUnpublishedAccess(): void {

    $node = Node::create([
      'title' => 'Test node',
      'type' => 'article',
      'status' => Node::NOT_PUBLISHED,
    ]);
    $node->save();

    $validToken = AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $node->id(),
      'value' => 'iAmValid',
      'expire' => -1,
    ]);
    $validToken->save();

    $query = <<<GQL
      query (\$path: String!) {
        page(path: \$path) {
          name
        }
      }
GQL;

    $variables = ['path' => $node->toUrl()->toString()];
    $response = $this->query($query, Json::encode($variables));
    $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');
    $this->assertEmpty($this->jsonDecode($response->getBody())['data']['page']);

    $query = <<<GQL
      query (\$path: String!, \$token: String!) {
        accessUnpublishedToken (auHash: \$token)
        page(path: \$path) {
          name
        }
      }
GQL;

    $variables = ['path' => $node->toUrl()->toString(), 'token' => 'iAmValid'];
    $response = $this->query($query, Json::encode($variables));
    $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');

    $this->assertEqualsCanonicalizing(['name' => 'Test node'], $this->jsonDecode($response->getBody())['data']['page']);
  }

  /**
   * Tests the article with an expired teaser image.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testExpiredImage(): void {

    $this->loadMediaByUuid('17965877-27b2-428f-8b8c-7dccba9786e5')
      ->setUnpublished()
      ->save();

    MediaType::load('image')
      ->setThirdPartySetting('media_expire', 'fallback_media', '05048c57-942d-4251-ad12-ce562f8c79a0')
      ->save();

    $query = <<<GQL
      query (\$path: String!) {
        page(path: \$path) {
          name
          ... on Article {
            teaser {
              image {
                name
                published
                fallbackMedia {
                  name
                }
              }
            }
          }
        }
      }
GQL;

    $node = \Drupal::service('entity.repository')->loadEntityByUuid('node', '0bd5c257-2231-450f-b4c2-ab156af7b78d');
    $variables = ['path' => $node->toUrl()->toString()];
    $response = $this->query($query, Json::encode($variables));
    $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');
    $this->assertEqualsCanonicalizing([
      'name' => 'Burda Launches Open-Source CMS Thunder',
      'teaser' => [
        'image' => [
          'name' => 'Thunder',
          'published' => FALSE,
          'fallbackMedia' => [
            'name' => 'Image 1',
          ],
        ],
      ],
    ], $this->jsonDecode($response->getBody())['data']['page']);
  }

  /**
   * Validates that non-existing entity links do not generate a warning.
   */
  public function testNonExistingEntityLinks(): void {
    $query = <<<GQL
      query (\$path: String!) {
        page(path: \$path) {
          entityLinks {
            versionHistory
            editForm
            canonical
          }
        }
      }
GQL;

    $variables = ['path' => '/user/1'];
    $response = $this->query($query, Json::encode($variables));
    $page = $this->jsonDecode($response->getBody());
    $this->assertArrayNotHasKey('errors', $page);
    // A null value means that the entity does not have the link.
    $this->assertNull($page['data']['page']['entityLinks']['versionHistory']);
    // An empty string means that the user does not have access.
    $this->assertSame('', $page['data']['page']['entityLinks']['editForm']);
    // A working entity link.
    $this->assertSame('/user/1', $page['data']['page']['entityLinks']['canonical']);

  }

  /**
   * Validates the thunder schema.
   */
  public function testValidSchema(): void {
    /** @var \Drupal\graphql\GraphQL\ValidatorInterface $validator */
    $validator = \Drupal::service('graphql.validator');

    /** @var \Drupal\graphql\Entity\ServerInterface $server */
    $server = \Drupal::entityTypeManager()->getStorage('graphql_server')->load('thunder_graphql');

    $this->assertEmpty($validator->validateSchema($server), "The schema 'thunder_graphql' is not valid.");
    $this->assertEmpty($validator->getOrphanedResolvers($server), "The schema 'thunder_graphql' contains orphaned resolvers.");
    $this->assertEmpty($validator->getMissingResolvers($server), "The schema 'thunder_graphql' contains types without a resolver.");
  }

  /**
   * Tests query of an unpublished channel.
   */
  public function testLabelAccess(): void {
    $this->loadTermByUuid('bfc251bc-de35-467d-af44-1f7a7012b845')
      ->setUnpublished()
      ->save();

    $query = <<<GQL
      query (\$path: String!) {
        page(path: \$path) {
          ... on Article {
            channel {
              name
            }
          }
        }
      }
GQL;

    $variables = ['path' => '/duis-autem-vel-eum-iriure'];
    $response = $this->query($query, Json::encode($variables));
    $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');

    $page = $this->jsonDecode($response->getBody());
    $this->assertArrayNotHasKey('errors', $page);
    $this->assertArrayHasKey('data', $page);
    $this->assertArrayHasKey('page', $page['data']);
    $this->assertArrayHasKey('channel', $page['data']['page']);
    $this->assertNull($page['data']['page']['channel']);
  }

}
