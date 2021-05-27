<?php

namespace Drupal\Tests\thunder_gqls\Functional;

use Drupal\access_unpublished\Entity\AccessToken;
use Drupal\Component\Serialization\Json;
use Drupal\node\Entity\Node;

/**
 * Test the schema.
 *
 * @group thunder_gqls
 */
class SchemaTest extends ThunderGqlsTestBase {

  /**
   * Tests the article schema.
   *
   * @param string $schema
   *   Schema name to test.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *
   * @dataProvider schemas
   */
  public function testSchema(string $schema) {
    $this->runAndTestQuery($schema);
  }

  /**
   * A data provider for testSchema.
   */
  public function schemas(): array {
    return [
      [
        'article',
      ],
      [
        'paragraphs',
      ],
      [
        'entities_with_term',
      ],
      [
        'menu',
      ],
      [
        'breadcrumb',
      ],
    ];
  }

  /**
   * Tests the article schema.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testUnpublishedAccess() {

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
    $this->assertEmpty(json_decode($response->getBody(), TRUE)['data']['page']);

    $query = <<<GQL
      query (\$path: String!, \$token: String!) {
        page(path: \$path, auHash: \$token) {
          name
        }
      }
GQL;

    $variables = ['path' => $node->toUrl()->toString(), 'token' => 'iAmValid'];
    $response = $this->query($query, Json::encode($variables));
    $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');

    $this->assertEqualsCanonicalizing(['name' => 'Test node'], json_decode($response->getBody(), TRUE)['data']['page']);
  }

}
