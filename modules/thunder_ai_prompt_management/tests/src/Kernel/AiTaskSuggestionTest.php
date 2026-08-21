<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_ai_prompt_management\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\thunder_ai_prompt_management\Entity\AIPrompt;
use Drupal\thunder_ai_prompt_management\Entity\AiTask;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests prompt discovery and button building for the AI task action.
 *
 * @coversDefaultClass \Drupal\thunder_ai_prompt_management\Plugin\FieldWidgetAction\AiTaskSuggestion
 * @group Thunder
 */
#[RunTestsInSeparateProcesses]
class AiTaskSuggestionTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'node',
    'text',
    'filter',
    'file',
    'key',
    'ai',
    'entity_blueprint',
    'field_widget_actions',
    'thunder_ai_prompt_management',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('ai_prompt_content');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['field', 'node', 'filter']);

    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    AiTask::create(['id' => 'headline', 'label' => 'Headline'])->save();
    AiTask::create(['id' => 'other', 'label' => 'Other'])->save();

    // User 1 is created first so it never owns the prompts under test.
    User::create(['uid' => 1, 'name' => 'admin'])->save();
    $this->setCurrentUser($this->createUser(['view ai_prompt_content']));
  }

  /**
   * Creates a prompt.
   */
  private function createPrompt(string $label, array $overrides = []): AIPrompt {
    $prompt = AIPrompt::create($overrides + [
      'label' => $label,
      'prompt' => 'Write a headline.',
      'model' => 'openai__gpt-4o',
      'ai_task' => 'headline',
      'entity_context' => ['node.article'],
      'status' => 1,
      'uid' => 1,
    ]);
    $prompt->save();
    return $prompt;
  }

  /**
   * Builds the plugin, wired to the node title field.
   */
  private function plugin(array $settings = ['ai_task' => 'headline']): object {
    $plugin = \Drupal::service('plugin.manager.field_widget_actions')
      ->createInstance('thunder_ai_task_suggestion', [
        'plugin_id' => 'thunder_ai_task_suggestion',
        'enabled' => TRUE,
        'button_label' => 'AI headline',
        'settings' => $settings,
      ]);
    $plugin->setFieldDefinition(
      \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article')['title']
    );
    return $plugin;
  }

  /**
   * Only prompts of the configured task are offered.
   */
  public function testFiltersByTask(): void {
    $this->createPrompt('Punchy');
    $this->createPrompt('Unrelated', ['ai_task' => 'other']);

    $article = Node::create(['type' => 'article', 'title' => 'A']);
    $labels = array_map(fn ($p) => $p->label(), $this->plugin()->loadPrompts($article));
    $this->assertSame(['Punchy'], array_values($labels));
  }

  /**
   * Unpublished prompts are never offered.
   */
  public function testExcludesUnpublished(): void {
    $this->createPrompt('Published');
    $this->createPrompt('Draft', ['status' => 0]);

    $article = Node::create(['type' => 'article', 'title' => 'A']);
    $labels = array_map(fn ($p) => $p->label(), $this->plugin()->loadPrompts($article));
    $this->assertSame(['Published'], array_values($labels));
  }

  /**
   * Prompts scoped to another entity type or bundle are not offered.
   *
   * The bundle comes from the entity; base fields report none of their own.
   */
  public function testFiltersByEntityContext(): void {
    $this->createPrompt('For articles', ['entity_context' => ['node.article']]);
    $this->createPrompt('For pages', ['entity_context' => ['node.page']]);
    $this->createPrompt('For any node', ['entity_context' => ['node.*']]);
    $this->createPrompt('For media', ['entity_context' => ['media.image']]);

    $article = Node::create(['type' => 'article', 'title' => 'A']);
    $labels = array_map(fn ($p) => $p->label(), $this->plugin()->loadPrompts($article));
    sort($labels);
    $this->assertSame(['For any node', 'For articles'], $labels);
  }

  /**
   * A task with no usable prompt renders no button at all.
   */
  public function testNoPromptsRendersNoButton(): void {
    $this->createPrompt('Wrong task', ['ai_task' => 'other']);
    $form = $this->buildButtons(Node::create(['type' => 'article', 'title' => 'A']));
    $this->assertSame([], array_keys($form));
  }

  /**
   * A single prompt renders one plain button, not a dropbutton.
   */
  public function testSinglePromptRendersPlainButton(): void {
    $prompt = $this->createPrompt('Only one');
    $form = $this->buildButtons(Node::create(['type' => 'article', 'title' => 'A']));

    $this->assertCount(1, $form);
    $button = reset($form);
    $this->assertNotSame('simple_actions', $button['#type'] ?? '');
    $this->assertSame($prompt->id(), $button['#ai_prompt_id']);
    $this->assertSame('AI headline', (string) $button['#value']);
  }

  /**
   * Several prompts are grouped into one dropbutton, one entry each.
   */
  public function testMultiplePromptsRenderDropbutton(): void {
    $punchy = $this->createPrompt('Punchy');
    $seo = $this->createPrompt('SEO');
    $form = $this->buildButtons(Node::create(['type' => 'article', 'title' => 'A']));

    $group = NULL;
    $originals = [];
    foreach ($form as $element) {
      if (($element['#type'] ?? '') === 'dropbutton') {
        $group = $element;
      }
      else {
        $originals[] = $element;
      }
    }

    $this->assertNotNull($group, 'the prompts are grouped into a dropbutton');
    $this->assertCount(2, $group['#links']);

    // Originals stay in the form tree as the real trigger, but hidden.
    $this->assertCount(2, $originals);
    foreach ($originals as $original) {
      $this->assertTrue($original['#printed'], 'the original button is hidden rather than removed');
    }

    $entries = [];
    foreach ($group['#links'] as $link) {
      $entries[(string) $link['title']['#ai_prompt_id']] = $link['title'];
    }
    // PHP coerces numeric string keys back to int, so normalise before compare.
    $this->assertSame(
      [(string) $punchy->id(), (string) $seo->id()],
      array_map('strval', array_keys($entries))
    );

    foreach ([$punchy, $seo] as $prompt) {
      $entry = $entries[(string) $prompt->id()];
      $this->assertSame($prompt->label(), (string) $entry['#value'], 'entry is labelled with the prompt');
      $this->assertNotEmpty($entry['#id'], 'entry carries an explicit #id');
      $this->assertTrue($entry['#ajax_processed'], 'entry had its #ajax bound while nested under #links');
      $this->assertArrayHasKey(
        $entry['#id'],
        $entry['#attached']['drupalSettings']['ajax'],
        'entry registers its AJAX settings under its own id'
      );
    }
    // The bug this guards against was invisible with only one button.
    $ids = array_column($entries, '#id');
    $this->assertSame($ids, array_unique($ids));
    $names = array_column($entries, '#name');
    $this->assertSame($names, array_unique($names));
  }

  /**
   * Dropbutton entries still work on a delta other than zero.
   *
   * Reading back by action_id alone misses the delta past the first item.
   */
  public function testDropbuttonEntriesSurviveNonZeroDelta(): void {
    $this->createPrompt('Punchy');
    $this->createPrompt('SEO');

    $form = $this->buildButtons(
      Node::create(['type' => 'article', 'title' => 'A']),
      NULL,
      ['delta' => 1]
    );

    $group = NULL;
    foreach ($form as $element) {
      if (($element['#type'] ?? '') === 'dropbutton') {
        $group = $element;
      }
    }
    $this->assertNotNull($group);
    $this->assertCount(2, $group['#links']);

    foreach ($group['#links'] as $link) {
      $entry = $link['title'];
      $this->assertSame('button', $entry['#type'] ?? NULL, 'the entry is a real button, not an empty stub');
      $this->assertNotEmpty($entry['#name'] ?? NULL);
      $this->assertTrue($entry['#ajax_processed'] ?? FALSE, 'the entry has its #ajax bound');
    }
  }

  /**
   * The button keeps its own AJAX callback.
   *
   * Only attaches clearErrorsForAction() when an AJAX callback is declared.
   */
  public function testButtonUsesOwnAjaxCallbackAndSuppressesRequiredErrors(): void {
    $this->createPrompt('Punchy');
    $plugin = $this->plugin();
    $this->assertSame('aiTaskSuggestionAjax', $plugin->getAjaxCallback());

    $buttons = $this->buildButtons(Node::create(['type' => 'article', 'title' => 'A']), $plugin);
    $button = reset($buttons);
    $this->assertSame([$plugin, 'aiTaskSuggestionAjax'], $button['#ajax']['callback']);
    $this->assertContains('::validateForm', $button['#validate']);
    $this->assertEquals(
      [$plugin, 'clearErrorsForAction'],
      $button['#validate'][1],
      'required-but-empty errors are stripped when generating'
    );
  }

  /**
   * Runs actionButton() and returns whatever it added to the form.
   */
  private function buildButtons(Node $node, ?object $plugin = NULL, array $context = []): array {
    $form = [];
    $formObject = \Drupal::entityTypeManager()->getFormObject('node', 'default');
    $formObject->setEntity($node);
    $form_state = new FormState();
    $form_state->setFormObject($formObject);

    // Callers that assert on the plugin must pass the same instance back in.
    $plugin ??= $this->plugin();
    $method = new \ReflectionMethod($plugin, 'actionButton');
    $method->invokeArgs($plugin, [&$form, $form_state, ['items' => $node->get('title')] + $context]);
    return $form;
  }

}
