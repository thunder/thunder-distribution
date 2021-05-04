<?php

namespace Drupal\Tests\thunder_gqls\Functional;

/**
 * Test the schema.
 *
 * @group thunder_gqls
 */
class SchemaTest extends ThunderGqlsTestBase {

  /**
   * Tests the article schema.
   *
   * @param string $schema
   *   Schema name to test.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *
   * @dataProvider schemas
   */
  public function testSchema(string $schema) {
    $this->runAndTestQuery($schema);
  }

  /**
   * A data provider for testSchema.
   */
  public function schemas(): array {
    return [
      [
        'article',
      ],
      [
        'paragraphs',
      ],
      [
        'entities_with_term',
      ],
      [
        'menu',
      ],
      [
        'breadcrumb',
      ],
    ];
  }

}
