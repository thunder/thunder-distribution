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
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testSchema() {
    $tags = $this->config('metatag.metatag_defaults.node__article')->get('tags');
    unset($tags['schema_article_date_modified'], $tags['schema_article_image']);
    $this->config('metatag.metatag_defaults.node__article')->set('tags', $tags)
      ->save();

    $this->drupalLogin($this->graphqlUser);
    $this->runAndTestQuery('jsonld');
  }

}
