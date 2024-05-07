<?php

namespace Drupal\Tests\thunder_gqls\Functional;

use Drupal\Component\Serialization\Json;

/**
 * Test the redirect endpoint.
 *
 * @group Thunder
 */
class RedirectSchemaTest extends ThunderGqlsTestBase {

  /**
   * A node entity, that is set to unpublished in setup method.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $unpublishedEntity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->unpublishedEntity = $this->loadNodeByUuid('94ad928b-3ec8-4bcb-b617-ab1607bf69cb');
    $this->unpublishedEntity->set('moderation_state', 'unpublished')->save();
  }

  /**
   * Test redirect to alias, depending on redirect settings.
   */
  public function testAlias(): void {
    $query = $this->getQueryFromFile('redirect');
    $path = '/node/' . $this->loadNodeByUuid('36b2e2b2-3df0-43eb-a282-d792b0999c07')->id();
    $variables = Json::encode(['path' => $path]);

    $this->config('redirect.settings')
      ->set('route_normalizer_enabled', TRUE)
      ->save();

    $response = $this->query($query, $variables);
    $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');

    $redirectResponseData = Json::decode($response->getBody())['data']['redirect'];
    $expectedResponse = [
      'url' => '/come-drupalcon-new-orleans',
      'status' => 301,
    ];

    $this->assertEqualsCanonicalizing($expectedResponse, $redirectResponseData, 'Not redirected to alias');

    $this->config('redirect.settings')
      ->set('route_normalizer_enabled', FALSE)
      ->save();

    // rebuild caches
    $this->container->get('cache.graphql.results')->deleteAll();

    $response = $this->query($query, $variables);
    $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');

    $redirectResponseData = Json::decode($response->getBody())['data']['redirect'];
    $expectedResponse = [
      'url' => $path,
      'status' => 200,
    ];

    $this->assertEqualsCanonicalizing($expectedResponse, $redirectResponseData, 'False redirect to alias');
  }

  /**
   * Tests the jsonld extension.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testRedirect(): void {
    $testCases = $this->redirectTestCases();
    $query = $this->getQueryFromFile('redirect');

    foreach ($testCases as $description => $testCase) {
      [$variables, $expectedResponse] = $testCase;

      $response = $this->query($query, Json::encode($variables));
      $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');

      $redirectResponseData = Json::decode($response->getBody())['data']['redirect'];
      $this->assertEqualsCanonicalizing($expectedResponse, $redirectResponseData, $description);
    }
  }

  /**
   * Redirect test cases.
   *
   * @return array[]
   *   The redirect test cases.
   */
  public function redirectTestCases(): array {
    return [
      'Basic redirect' => [
        [
          'path' => '/former-url',
        ],
        [
          'url' => 'https://www.google.com',
          'status' => 301,
        ],
      ],
      'Redirect from canonical' => [
        [
          'path' => '/node/' . $this->loadNodeByUuid('36b2e2b2-3df0-43eb-a282-d792b0999c07')->id(),
        ],
        [
          'url' => '/come-drupalcon-new-orleans',
          'status' => 301,
        ],
      ],
      'Redirect does not exist' => [
        [
          'path' => '/unknown-url',
        ],
        [
          'url' => '/unknown-url',
          'status' => 404,
        ],

      ],
      'No redirect, but valid path' => [
        [
          'path' => '/burda-launches-open-source-cms-thunder',
        ],
        [
          'url' => '/burda-launches-open-source-cms-thunder',
          'status' => 200,
        ],
      ],
      'Unpublished entity' => [
        [
          'path' => '/duis-autem-vel-eum-iriure',
        ],
        [
          'url' => '/duis-autem-vel-eum-iriure',
          'status' => 403,
        ],
      ],
    ];
  }

}
