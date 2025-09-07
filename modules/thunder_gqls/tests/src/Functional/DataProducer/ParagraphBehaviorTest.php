<?php

namespace Drupal\Tests\thunder_gqls\Unit\Plugin\GraphQL\DataProducer;

use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\Tests\graphql\Traits\DataProducerExecutionTrait;
use Drupal\Tests\thunder_gqls\Functional\ThunderGqlsTestBase;

/**
 * Test the schema.
 *
 * @group Thunder
 */
final class ParagraphBehaviorTest extends ThunderGqlsTestBase {

  use DataProducerExecutionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'paragraphs',
  ];

  /**
   * Returns a paragraph type with the provided behaviour enabled or disabled.
   *
   * @param string $pluginId
   * @param bool $enabled
   *
   * @return \Drupal\paragraphs\ParagraphsTypeInterface
   */
  private function createParagraphTypeMock(string $pluginId, bool $enabled): ParagraphsTypeInterface {
    $type = $this->createMock(ParagraphsTypeInterface::class);
    $type->method('hasEnabledBehaviorPlugin')
      ->with($pluginId)
      ->willReturn($enabled);
    return $type;
  }

  /**
   * Tests behavior when plugin is enabled.
   *
   * @covers \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ParagraphBehavior::resolve
   */
  public function testResolveReturnsBehaviorSettingWhenPluginEnabled(): void {
    $type = $this->createParagraphTypeMock('my_plugin', true);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getParagraphType')->willReturn($type);
    $paragraph->method('getBehaviorSetting')
      ->with('my_plugin', 'my_key', 'default_value')
      ->willReturn('expected_value');

    $result = $this->executeDataProducer('paragraph_behavior', [
      'paragraph' => $paragraph,
      'behavior_plugin_id' => 'my_plugin',
      'behavior_plugin_key' => 'my_key',
      'behavior_plugin_default' => 'default_value',
      'throw_on_missing_plugin' => true,
    ]);

    $this->assertSame('expected_value', $result);
  }

  /**
   * Tests behavior when plugin is disabled and throw enabled.
   *
   * @covers \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ParagraphBehavior::resolve
   */
  public function testResolveThrowsWhenPluginDisabledAndThrowEnabled(): void {
    $type = $this->createParagraphTypeMock('missing_plugin', false);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getParagraphType')->willReturn($type);
    $paragraph->expects($this->never())->method('getBehaviorSetting');

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Not enabled or invalid paragraphs behavior plugin.');

    $this->executeDataProducer('paragraph_behavior', [
      'paragraph' => $paragraph,
      'behavior_plugin_id' => 'missing_plugin',
      'behavior_plugin_key' => 'any_key',
      'behavior_plugin_default' => 'any_default',
      'throw_on_missing_plugin' => true,
    ]);
  }

  /**
   * Tests behavior when plugin is disabled and throw is disabled.
   *
   * @covers \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ParagraphBehavior::resolve
   */
  public function testResolveReturnsDefaultWhenPluginDisabledAndThrowDisabled(): void {
    $type = $this->createParagraphTypeMock('missing_plugin', false);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getParagraphType')->willReturn($type);
    $paragraph->expects($this->never())->method('getBehaviorSetting');

    $result = $this->executeDataProducer('paragraph_behavior', [
      'paragraph' => $paragraph,
      'behavior_plugin_id' => 'missing_plugin',
      'behavior_plugin_key' => 'any_key',
      'behavior_plugin_default' => 'fallback',
      'throw_on_missing_plugin' => false,
    ]);

    $this->assertSame('fallback', $result);
  }

  /**
   * Tests behavior when default value is NULL.
   *
   * @covers \Drupal\thunder_gqls\Plugin\GraphQL\DataProducer\ParagraphBehavior::resolve
   */
  public function testNullDefaultValue(): void {
    $type = $this->createParagraphTypeMock('missing_plugin', false);

    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getParagraphType')->willReturn($type);
    $paragraph->expects($this->never())->method('getBehaviorSetting');

    $result = $this->executeDataProducer('paragraph_behavior', [
      'paragraph' => $paragraph,
      'behavior_plugin_id' => 'missing_plugin',
      'behavior_plugin_key' => 'any_key',
      'behavior_plugin_default' => null,
      'throw_on_missing_plugin' => false,
    ]);

    $this->assertNull($result);
  }

}
