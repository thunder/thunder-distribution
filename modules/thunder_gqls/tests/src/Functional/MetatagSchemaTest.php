<?php

namespace Drupal\Tests\thunder_gqls\Functional;

/**
 * Test the metatag schema.
 *
 * @group Thunder
 */
class MetatagSchemaTest extends ThunderGqlsTestBase {

  /**
   * Tests the metatag extension.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testSchema() {
    $tags = $this->config('metatag.metatag_defaults.node__article')->get('tags');
    unset($tags['schema_article_date_modified'], $tags['schema_article_image'], $tags['schema_article_publisher']);
    $this->config('metatag.metatag_defaults.node__article')->set('tags', $tags)
      ->save();

    $this->drupalLogin($this->graphqlUser);
    $this->runAndTestQuery('metatags');
  }

}
