<?php

declare(strict_types=1);

namespace Drupal\Tests\thunder_ai_prompt_management\Kernel;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\KernelTests\KernelTestBase;
use Drupal\thunder_ai_prompt_management\Entity\AIPrompt;
use Drupal\thunder_ai_prompt_management\Entity\AiTask;
use Drupal\thunder_ai_prompt_management\ThunderPromptSource;

/**
 * Tests the assistant UI prompt source backed by ai_prompt_content.
 *
 * @group Thunder
 */
class ThunderPromptSourceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'key',
    'file',
    'ai',
    'ai_test',
    'ai_assistant_api',
    'ai_chatbot_assistant_ui',
    'thunder_ai_prompt_management',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('ai_prompt_content');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['system', 'user']);
  }

  /**
   * The service provider swaps the assistant UI's prompt source for this one.
   */
  public function testServiceOverride(): void {
    $this->assertInstanceOf(
      ThunderPromptSource::class,
      $this->container->get('ai_chatbot_assistant_ui.prompt_source'),
    );
  }

  /**
   * Prompts are grouped by their ai_task, with no variables.
   */
  public function testSlashGroupsGroupByTask(): void {
    $this->createTask('seo', 'SEO');
    $this->createPrompt('Meta description', 'Suggest a meta description.', 'seo');

    $groups = $this->source()->getSlashGroups(NULL, NULL, new CacheableMetadata());
    $this->assertCount(1, $groups);
    $this->assertSame('seo', $groups[0]['id']);
    $this->assertSame('SEO', $groups[0]['label']);
    $this->assertSame([], $groups[0]['variables']);
    $this->assertSame('Meta description', $groups[0]['prompts'][0]['label']);
    $this->assertSame('Suggest a meta description.', $groups[0]['prompts'][0]['prompt']);
  }

  /**
   * Prompts are filtered by the entity open in the edit form.
   */
  public function testSlashGroupsFilterByEntityContext(): void {
    $this->createTask('seo', 'SEO');
    $this->createPrompt('Article prompt', 'For articles.', 'seo', ['node.article']);
    $this->createPrompt('Image prompt', 'For images.', 'seo', ['media.image']);

    $article = $this->source()->getSlashGroups('node', 'article', new CacheableMetadata());
    $this->assertCount(1, $article);
    $this->assertCount(1, $article[0]['prompts']);
    $this->assertSame('Article prompt', $article[0]['prompts'][0]['label']);

    $image = $this->source()->getSlashGroups('media', 'image', new CacheableMetadata());
    $this->assertSame('Image prompt', $image[0]['prompts'][0]['label']);

    $this->assertSame([], $this->source()->getSlashGroups('node', 'page', new CacheableMetadata()));
  }

  /**
   * A "type.*" entity_context value matches every bundle of that type.
   */
  public function testWildcardEntityContextMatchesEveryBundle(): void {
    $this->createTask('seo', 'SEO');
    $this->createPrompt('Any node', 'Works on any node bundle.', 'seo', ['node.*']);

    $article = $this->source()->getSlashGroups('node', 'article', new CacheableMetadata());
    $this->assertCount(1, $article[0]['prompts']);

    $page = $this->source()->getSlashGroups('node', 'page', new CacheableMetadata());
    $this->assertCount(1, $page[0]['prompts']);

    $this->assertSame([], $this->source()->getSlashGroups('media', 'image', new CacheableMetadata()));
  }

  /**
   * A prompt with no entity_context restriction matches every context.
   */
  public function testUnrestrictedPromptMatchesEveryContext(): void {
    $this->createTask('seo', 'SEO');
    $this->createPrompt('Any context', 'Works anywhere.', 'seo');

    $this->assertCount(1, $this->source()->getSlashGroups('node', 'article', new CacheableMetadata()));
  }

  /**
   * getSlashGroups() adds the prompt's and its task's cache tags.
   */
  public function testSlashGroupsCacheability(): void {
    $this->createTask('seo', 'SEO');
    $task = AiTask::load('seo');
    $this->assertNotNull($task);
    $prompt = $this->createPrompt('Meta description', 'Suggest a meta description.', 'seo');

    $cacheability = new CacheableMetadata();
    $this->source()->getSlashGroups(NULL, NULL, $cacheability);

    $tags = $cacheability->getCacheTags();
    foreach ($prompt->getCacheTags() as $tag) {
      $this->assertContains($tag, $tags);
    }
    foreach ($task->getCacheTags() as $tag) {
      $this->assertContains($tag, $tags);
    }
  }

  /**
   * Suggestions preserve the configured order and skip non-matching prompts.
   */
  public function testSuggestionsRespectOrderAndFilter(): void {
    $this->createTask('seo', 'SEO');
    $first = $this->createPrompt('First', 'One.', 'seo', ['node.article']);
    $second = $this->createPrompt('Second', 'Two.', 'seo');
    $this->createPrompt('Hidden', 'Hidden.', 'seo', ['media.image']);

    $suggestions = $this->source()->getSuggestions(
      [(string) $first->id(), (string) $second->id()],
      'node',
      'article',
      new CacheableMetadata(),
    );
    $this->assertCount(2, $suggestions);
    $this->assertSame('First', $suggestions[0]['label']);
    $this->assertSame('Second', $suggestions[1]['label']);
    $this->assertSame([], $suggestions[0]['variables']);
  }

  /**
   * Unpublished prompts are never offered.
   */
  public function testUnpublishedPromptsAreExcluded(): void {
    $this->createTask('seo', 'SEO');
    $this->createPrompt('Draft', 'Draft prompt.', 'seo', [], FALSE);

    $this->assertSame([], $this->source()->getSlashGroups(NULL, NULL, new CacheableMetadata()));
  }

  /**
   * A prompt with no ai_task still appears, under a fallback group.
   */
  public function testPromptWithoutTaskIsGrouped(): void {
    AIPrompt::create([
      'label' => 'Untasked',
      'prompt' => 'No task assigned.',
      'model' => 'openai__gpt-4o',
    ])->save();

    $groups = $this->source()->getSlashGroups(NULL, NULL, new CacheableMetadata());
    $this->assertCount(1, $groups);
    $this->assertSame('_none', $groups[0]['id']);
    $this->assertSame('Untasked', $groups[0]['prompts'][0]['label']);
  }

  /**
   * ResolvePrompt returns the raw text - thunder prompts have no variables.
   */
  public function testResolvePrompt(): void {
    $this->createTask('seo', 'SEO');
    $prompt = $this->createPrompt('Meta', 'Suggest a meta description.', 'seo');

    $this->assertSame(
      'Suggest a meta description.',
      $this->source()->resolvePrompt((string) $prompt->id(), []),
    );
  }

  /**
   * Instantiates the source against the container's entity type manager.
   */
  private function source(): ThunderPromptSource {
    return new ThunderPromptSource($this->container->get('entity_type.manager'));
  }

  /**
   * Creates an ai task config entity.
   */
  private function createTask(string $id, string $label): void {
    AiTask::create([
      'id' => $id,
      'label' => $label,
      'description' => '',
    ])->save();
  }

  /**
   * Creates an ai prompt.
   */
  private function createPrompt(string $label, string $prompt, string $task, array $context = [], bool $published = TRUE): AIPrompt {
    $prompt = AIPrompt::create([
      'label' => $label,
      'prompt' => $prompt,
      'ai_task' => $task,
      'model' => 'openai__gpt-4o',
      'entity_context' => $context,
      'status' => $published,
    ]);
    $prompt->save();
    return $prompt;
  }

}
