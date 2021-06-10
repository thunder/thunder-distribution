<?php

namespace Drupal\Tests\thunder_gqls\Functional;

/**
 * Test the schema.
 *
 * @group thunder_gqls
 */
class JsonLdSchemaTest extends ThunderGqlsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'schema_article',
  ];

  /**
   * Tests the jsonld extension.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testSchema() {
    $tags = $this->config('metatag.metatag_defaults.node__article')->get('tags');
    $tags['schema_article_type'] = 'Article';
    $this->config('metatag.metatag_defaults.node__article')->set('tags', $tags)
      ->save();

    $extensions = $this->config('graphql.graphql_servers.thunder_graphql')->get('schema_configuration.thunder.extensions');
    $extensions['thunder_jsonld'] = 'thunder_jsonld';
    $this->config('graphql.graphql_servers.thunder_graphql')->set('schema_configuration.thunder.extensions', $extensions)
      ->save();

    $this->drupalLogin($this->graphqlUser);
    $this->runAndTestQuery('jsonld');
  }

}
