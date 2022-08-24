<?php

namespace Drupal\Tests\thunder_gqls\Kernel\DataProducer;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * Tests the paragraphs behavior data producer.
 *
 * @group Thunder
 */
class ParagraphsBehaviorTest extends GraphQLTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs',
    'entity_reference_revisions',
    'paragraphs_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('paragraph');
    $this->installSchema('system', ['sequences']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');
  }

  /**
   * Tests the behavior settings API.
   */
  public function testBehaviorSettings() {
    // Create a paragraph type.
    $paragraph_type = ParagraphsType::create(array(
      'label' => 'test_text',
      'id' => 'test_text',
      'behavior_plugins' => [
        'test_text_color' => [
          'enabled' => TRUE,
        ]
      ],
    ));
    $paragraph_type->save();

    // Create a paragraph and set its feature settings.
    $paragraph = Paragraph::create([
      'type' => 'test_text',
    ]);
    $feature_settings = [
      'test_text_color' => [
        'text_color' => 'red'
      ],
    ];
    $paragraph->setAllBehaviorSettings($feature_settings);
    $paragraph->save();

    $result = $this->executeDataProducer('paragraph_behavior', [
      'paragraph' => $paragraph,
      'behavior_plugin' => 'test_text_color',
      'behavior_setting' => 'text_color'
    ]);

    $this->assertEquals('red', $result);

  }

}
