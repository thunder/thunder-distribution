<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * Tests the paragraphs behavior data producer.
 *
 * @group Thunder
 */
class ParagraphsBehaviorTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'thunder_gqls',
    'paragraphs',
    'entity_reference_revisions',
    'paragraphs_test',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('paragraph');
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');
  }

  /**
   * Tests the behavior settings API.
   */
  public function testBehaviorSettings(): void {
    // Create a paragraph type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'test_text',
      'id' => 'test_text',
      'behavior_plugins' => [
        'test_text_color' => [
          'enabled' => TRUE,
        ],
      ],
    ]);
    $paragraph_type->save();

    // Create a paragraph and set empty feature settings to test plugin_default.
    $paragraph = Paragraph::create([
      'type' => 'test_text',
    ]);
    $feature_settings = [];
    $paragraph->setAllBehaviorSettings($feature_settings);
    $paragraph->save();

    $result = $this->executeDataProducer('paragraph_behavior', [
      'paragraph' => $paragraph,
      'behavior_plugin_id' => 'test_text_color',
      'behavior_plugin_key' => 'text_color',
      'behavior_plugin_default' => 'blue',
    ]);
    $this->assertEquals('blue', $result);

    // Create a paragraph and set its feature settings.
    $paragraph = Paragraph::create([
      'type' => 'test_text',
    ]);
    $feature_settings = [
      'test_text_color' => [
        'text_color' => 'red',
      ],
    ];
    $paragraph->setAllBehaviorSettings($feature_settings);
    $paragraph->save();

    $result = $this->executeDataProducer('paragraph_behavior', [
      'paragraph' => $paragraph,
      'behavior_plugin_id' => 'test_text_color',
      'behavior_plugin_key' => 'text_color',
      'behavior_plugin_default' => 'blue',
    ]);

    // Now we should not get the default value.
    $this->assertEquals('red', $result);
  }

}
