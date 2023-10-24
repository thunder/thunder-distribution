<?php

namespace Drupal\Tests\thunder_gqls\Functional;

/**
 * Test the schema.
 *
 * @group Thunder
 */
class JsonLdSchemaTest extends ThunderGqlsTestBase {

  /**
   * Tests the jsonld extension.
   *
   * @group NoUpdate
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testSchema(): void {
    $tags = $this->config('metatag.metatag_defaults.node__article')->get('tags');
    unset($tags['schema_article_date_modified'], $tags['schema_article_image'], $tags['schema_article_publisher']);
    $this->config('metatag.metatag_defaults.node__article')->set('tags', $tags)
      ->save();

    $schema = 'jsonld';

    $query = $this->getQueryFromFile($schema);
    $variables = $this->getVariablesFromFile($schema);

    $responseData = $this->jsonDecode(strip_tags($this->getResponseData($query, $variables)['jsonld']));
    $expectedData = $this->jsonDecode(strip_tags($this->jsonDecode($this->getExpectedResponseFromFile($schema))['data']['jsonld']));

    $this->assertEqualsCanonicalizing($expectedData, $responseData);

  }

}
