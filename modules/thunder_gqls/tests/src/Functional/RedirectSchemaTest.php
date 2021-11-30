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

    $responseData = Json::decode($response->getBody())['data'];

    $this->assertEqualsCanonicalizing($expectedResponse, $responseData);
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
          'redirect' => [
            'url' => 'https://www.google.com',
            'status' => '301',
          ],
        ],
      ],
      'Redirect does not exist' => [
        [
          'path' => '/unknown-url',
        ],
        [
          'redirect' => [
            'url' => '/unknown-url',
            'status' => '404',
          ],
        ],
      ],
      'No redirect' => [
        [
          'path' => '/burda-launches-open-source-cms-thunder',
        ],
        [
          'redirect' => [
            'url' => '/burda-launches-open-source-cms-thunder',
            'status' => '200',
          ],
        ],
      ],
    ];
  }

}
