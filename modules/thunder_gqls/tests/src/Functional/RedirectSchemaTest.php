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
   * Tests the jsonld extension.
   *
   * @dataProvider redirectTestCases
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testRedirect(array $variables, array $expectedResponse) {
    $schema = "redirect";
    $query = $this->getQueryFromFile($schema);

    $response = $this->query($query, Json::encode($variables));
    $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');

    $redirectResponseData = Json::decode($response->getBody())['data']['redirect'];
    $this->assertEqualsCanonicalizing($expectedResponse, $redirectResponseData);
  }

  /**
   * A data provider for testRedirect.
   */
  public function redirectTestCases() {
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
      'unpublished entity' => [
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
