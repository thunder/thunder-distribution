<?php

namespace Drupal\Tests\thunder_vgwort\Functional;

use Drupal\Tests\thunder_gqls\Functional\ThunderGqlsTestBase;

/**
 * Test the integration of vgwort schema.
 *
 * @group Thunder
 */
class VgWortSchemaTest extends ThunderGqlsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_vgwort',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Add schema extensions for VG Wort.
    $graphQlServerSettings = $this->config('graphql.graphql_servers.thunder_graphql');
    $extensions = array_merge($graphQlServerSettings->get('schema_configuration.thunder.extensions'),
      [
        'vgwort' => 'vgwort',
        'thunder_vgwort' => 'thunder_vgwort',
      ]
    );

    $graphQlServerSettings->set('schema_configuration.thunder.extensions', $extensions)->save();

    // Add publisher_id and image_domain to VG Wort settings.
    $this->config('vgwort.settings')
      ->set('publisher_id', '123456789')
      ->set('image_domain', 'www.example.com')
      ->save();
  }

  /**
   * Tests the article schema.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testSchema(): void {
    $this->runAndTestQuery('article_vgwort');
  }

}
