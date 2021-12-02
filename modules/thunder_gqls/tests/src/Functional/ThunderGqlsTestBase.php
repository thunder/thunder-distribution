<?php

namespace Drupal\Tests\thunder_gqls\Functional;

use Drupal\Tests\thunder\Functional\ThunderTestBase;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * The base class for all functional Thunder GraphQl schema tests.
 */
abstract class ThunderGqlsTestBase extends ThunderTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
    'thunder_testing_demo',
  ];

  /**
   * This can be removed, once the linked issue is resolved.
   *
   * @todo See https://github.com/drupal-graphql/graphql/issues/1177.
   *
   * {@inheritdoc}
   */
  protected static $configSchemaCheckerExclusions = [
    'graphql.graphql_servers.thunder_graphql',
  ];

  /**
   * User with graphql request privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $graphqlUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->graphqlUser = $this->drupalCreateUser([
      'execute thunder_graphql arbitrary graphql requests',
      'access content',
      'access user profiles',
      'view media',
      'view published terms in channel',
      'view published terms in tags',
    ]);
    $this->drupalLogin($this->graphqlUser);

  }

  /**
   * Queries the graphql api.
   *
   * @param string $query
   *   The GraphQl query to execute.
   * @param string $variables
   *   The variables for the query.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function query(string $query, string $variables): ResponseInterface {
    $urlGenerator = $this->container->get('url_generator');
    $url = $urlGenerator->generate('graphql.query.thunder_graphql');

    $requestOptions = [];
    $requestOptions[RequestOptions::HEADERS]['Content-Type'] = 'application/json';
    $requestOptions[RequestOptions::COOKIES] = $this->getSessionCookies();
    $requestOptions[RequestOptions::JSON]['query'] = $query;
    $requestOptions[RequestOptions::JSON]['variables'] = $variables;

    return $this->getHttpClient()->request('POST', $this->getAbsoluteUrl($url), $requestOptions);
  }

  /**
   * Get the path to the directory containing test query files.
   *
   * @return string
   *   The path to the collection of test query files.
   */
  protected function getQueriesDirectory() {
    /** @var \Drupal\Core\Extension\ExtensionPathResolver $extensionPathResolver */
    $extensionPathResolver = \Drupal::service('extension.path.resolver');
    return $extensionPathResolver->getPath('module', explode('\\', get_class($this))[2]) . '/tests/examples';
  }

  /**
   * Retrieve the GraphQL query stored in a file as string.
   *
   * @param string $name
   *   The example name.
   *
   * @return string
   *   The graphql query string.
   */
  public function getQueryFromFile(string $name): string {
    return file_get_contents($this->getQueriesDirectory() . '/' . $name . '.query.graphql');
  }

  /**
   * Retrieve the GraphQL variables stored in a file as string.
   *
   * @param string $name
   *   The example name.
   *
   * @return string
   *   The graphql variables string.
   */
  public function getVariablesFromFile(string $name): string {
    return file_get_contents($this->getQueriesDirectory() . '/' . $name . '.variables.json');
  }

  /**
   * Retrieve the GraphQL response stored in a file as string.
   *
   * @param string $name
   *   The example name.
   *
   * @return string
   *   The graphql response string.
   */
  public function getExpectedResponseFromFile(string $name): string {
    return file_get_contents($this->getQueriesDirectory() . '/' . $name . '.response.json');
  }

  /**
   * Execute query and compare to expected response.
   *
   * @param string $schema
   *   The schema to test.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function runAndTestQuery(string $schema): void {
    $query = $this->getQueryFromFile($schema);
    $variables = $this->getVariablesFromFile($schema);

    $response = $this->query($query, $variables);

    $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');

    $responseData = json_decode($response->getBody(), TRUE)['data'];
    $expectedData = json_decode(
      $this->getExpectedResponseFromFile($schema),
      TRUE
    )['data'];

    $this->assertEqualsCanonicalizing($expectedData, $responseData);
  }

}
