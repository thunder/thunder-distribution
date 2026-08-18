<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_ai_prompt_management\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\entity_blueprint\Exception\BlueprintException;
use Drupal\thunder_ai_prompt_management\EntityContextPromptBuilderInterface;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the EntityContextPromptBuilder service.
 *
 * @coversDefaultClass \Drupal\thunder_ai_prompt_management\EntityContextPromptBuilder
 * @group Thunder
 */
#[RunTestsInSeparateProcesses]
class EntityContextPromptBuilderTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'filter',
    'entity_test',
    'entity_blueprint',
    'thunder_ai_prompt_management',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
    $this->installConfig(['filter', 'text']);
    // entity_blueprint's schema builder gates on the bundle's create access.
    $this->setUpCurrentUser([], [], TRUE);
  }

  /**
   * @covers ::build
   */
  public function testBuildWithoutEntityLeavesPromptUnchanged(): void {
    $this->assertSame('Suggest a title.', $this->builder()->build('Suggest a title.'));
  }

  /**
   * @covers ::build
   */
  public function testBuildWithEntityAppendsBlueprintSchema(): void {
    $entity = $this->container->get('entity_type.manager')
      ->getStorage('entity_test')
      ->create(['name' => 'Test entity']);
    $entity->save();

    $result = $this->builder()->build('Suggest a title.', $entity);

    $this->assertStringStartsWith('Suggest a title.', $result);
    $this->assertStringContainsString('The editor has a entity_test/entity_test open for editing. Its schema:', $result);
    $this->assertStringContainsString('Its current content:', $result);

    [, $rest] = explode("Its schema:\n", $result, 2);
    [$schema_json, $content_json] = explode("\n\nIts current content:\n", $rest, 2);
    $schema = Json::decode($schema_json);
    $this->assertSame('entity_test', $schema['entity_type']);
    $this->assertSame('entity_test', $schema['bundle']);

    $serialized = Json::decode($content_json);
    $this->assertSame('entity_test', $serialized['entity_type']);
    $this->assertSame((int) $entity->id(), $serialized['_root_entity']['value']);
  }

  /**
   * @covers ::build
   */
  public function testBuildWithEntityThrowsWhenAccessDenied(): void {
    $entity = $this->container->get('entity_type.manager')
      ->getStorage('entity_test')
      ->create(['name' => 'Test entity']);
    $entity->save();

    // Swap the admin bypass for a user without create access to the bundle.
    $this->setUpCurrentUser([], []);

    $this->expectException(BlueprintException::class);
    $this->builder()->build('Suggest a title.', $entity);
  }

  /**
   * Gets the service under test.
   */
  private function builder(): EntityContextPromptBuilderInterface {
    return $this->container->get('thunder_ai_prompt_management.entity_context_prompt_builder');
  }

}
