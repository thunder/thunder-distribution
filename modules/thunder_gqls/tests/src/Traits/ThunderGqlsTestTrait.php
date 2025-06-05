<?php

namespace Drupal\Tests\thunder_gqls\Traits;

use Drupal\Core\Url;
use Drupal\Tests\ApiRequestTrait;
use Drupal\Tests\BrowserHtmlDebugTrait;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Use this trait query your GraphQL endpoint.
 */
trait ThunderGqlsTestTrait {
  use ApiRequestTrait;
  use BrowserHtmlDebugTrait;

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

    $responseData = $this->getResponseData($query, $variables);

    $expectedData = $this->jsonDecode($this->getExpectedResponseFromFile($schema))['data'];

    $this->assertEqualsCanonicalizing(
      $expectedData,
      $responseData,
      'The expected schema for "' . $schema . '" did not match the result.'
    );
  }

  /**
   * Get response data.
   *
   * @param string $query
   *   The graphql jsonld query.
   * @param string $variables
   *   The variables for the query.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException|\JsonException
   *   If the json is invalid or the request failed.
   *  @phpstan-ignore-next-line
   */
  protected function getResponseData(string $query, string $variables) {
    $response = $this->query($query, $variables);
    $this->assertEquals(200, $response->getStatusCode(), 'Response not 200');

    return $this->jsonDecode($response->getBody())['data'];
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
    $requestOptions = [];
    $requestOptions[RequestOptions::HEADERS]['Content-Type'] = 'application/json';
    $requestOptions[RequestOptions::JSON]['query'] = $query;
    $requestOptions[RequestOptions::JSON]['variables'] = $variables;

    return $this->makeApiRequest('POST', Url::fromRoute('graphql.query.thunder_graphql'), $requestOptions);
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
  protected function getQueryFromFile(string $name): string {
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
  protected function getVariablesFromFile(string $name): string {
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
  protected function getExpectedResponseFromFile(string $name): string {
    return file_get_contents($this->getQueriesDirectory() . '/' . $name . '.response.json');
  }

  /**
   * Decodes a json string.
   *
   * @param string $json
   *   The json string.
   *
   * @return mixed
   *   The decoded json.
   */
  protected function jsonDecode(string $json) {
    return json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
  }

}
