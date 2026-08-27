<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_ai_prompt_management\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\thunder_ai_prompt_management\EntityContext;

/**
 * @coversDefaultClass \Drupal\thunder_ai_prompt_management\EntityContext
 * @group Thunder
 */
class EntityContextTest extends UnitTestCase {

  /**
   * @covers ::encode
   */
  public function testEncodeWithBundle(): void {
    $this->assertSame('node.article', EntityContext::encode('node', 'article'));
  }

  /**
   * @covers ::encode
   */
  public function testEncodeWithWildcard(): void {
    $this->assertSame('node.*', EntityContext::encode('node', '*'));
  }

  /**
   * @covers ::decode
   */
  public function testDecodeSplitsTypeAndBundle(): void {
    $this->assertSame(['node', 'article'], EntityContext::decode('node.article'));
  }

  /**
   * @covers ::decode
   */
  public function testDecodeWildcard(): void {
    $this->assertSame(['node', '*'], EntityContext::decode('node.*'));
  }

  /**
   * @covers ::decode
   */
  public function testDecodeMissingBundlePadsWildcard(): void {
    $this->assertSame(['node', '*'], EntityContext::decode('node'));
  }

  /**
   * @covers ::groupByType
   */
  public function testGroupByTypeGroupsMultipleBundles(): void {
    $grouped = EntityContext::groupByType(['node.article', 'node.page', 'media.image']);
    $this->assertSame(['node' => ['article', 'page'], 'media' => ['image']], $grouped);
  }

  /**
   * @covers ::groupByType
   */
  public function testGroupByTypeEmptyInput(): void {
    $this->assertSame([], EntityContext::groupByType([]));
  }

  /**
   * @covers ::candidates
   */
  public function testCandidatesWithBundle(): void {
    $this->assertSame(['node.*', 'node.article'], EntityContext::candidates('node', 'article'));
  }

  /**
   * @covers ::candidates
   */
  public function testCandidatesWithoutBundle(): void {
    $this->assertSame(['node.*'], EntityContext::candidates('node', NULL));
  }

  /**
   * @covers ::matches
   */
  public function testMatchesNullEntityTypeReturnsTrue(): void {
    $this->assertTrue(EntityContext::matches(['node.article'], NULL, 'article'));
  }

  /**
   * @covers ::matches
   */
  public function testMatchesNullBundleReturnsTrue(): void {
    $this->assertTrue(EntityContext::matches(['node.article'], 'node', NULL));
  }

  /**
   * @covers ::matches
   */
  public function testMatchesEmptyContextsReturnsTrue(): void {
    $this->assertTrue(EntityContext::matches([], 'node', 'article'));
  }

  /**
   * @covers ::matches
   */
  public function testMatchesWildcardContextMatches(): void {
    $this->assertTrue(EntityContext::matches(['node.*'], 'node', 'page'));
  }

  /**
   * @covers ::matches
   */
  public function testMatchesExactContextMatches(): void {
    $this->assertTrue(EntityContext::matches(['node.article'], 'node', 'article'));
  }

  /**
   * @covers ::matches
   */
  public function testMatchesWrongBundleFails(): void {
    $this->assertFalse(EntityContext::matches(['node.article'], 'node', 'page'));
  }

  /**
   * @covers ::matches
   */
  public function testMatchesWrongEntityTypeFails(): void {
    $this->assertFalse(EntityContext::matches(['media.image'], 'node', 'article'));
  }

}
