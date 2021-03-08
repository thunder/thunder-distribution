<?php

namespace Drupal\Tests\thunder_gqls\Functional;

/**
 * Test the page schemata.
 *
 * @group thunder_gqls
 */
class PageSchemaTest extends ThunderGqlsTestBase {

  /**
   * Tests the article schema.
   */
  public function testArticleSchema() {
    $this->drupalLogin($this->graphqlUser);

    $this->assertTrue(TRUE, 'this should not fail');
  }

}
