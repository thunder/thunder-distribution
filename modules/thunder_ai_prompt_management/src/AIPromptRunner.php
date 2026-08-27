<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface;
use Drupal\entity_blueprint\Exception\BlueprintException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Runs an AI prompt against an entity and returns the generated text.
 */
final class AIPromptRunner {

  use AutowireTrait;
  use StringTranslationTrait;

  /**
   * Asks for several candidate answers instead of one.
   */
  private const SUGGESTIONS_INSTRUCTION = <<<'TEXT'
Answer with suggestions only. Do not repeat the task description and do not add
any other text. Do not include any explanations, only provide a RFC8259
compliant JSON response following this format without deviation:
[
  {"suggestion": "First suggested text"},
  {"suggestion": "Second suggested text"}
]
TEXT;

  public function __construct(
    #[Autowire(service: 'ai.provider')]
    private readonly AiProviderPluginManager $providerPluginManager,
    #[Autowire(service: 'thunder_ai_prompt_management.entity_context_prompt_builder')]
    private readonly EntityContextPromptBuilderInterface $promptBuilder,
    #[Autowire(service: 'ai.prompt_json_decode')]
    private readonly PromptJsonDecoderInterface $promptJsonDecoder,
    private readonly MessengerInterface $messenger,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Runs a prompt and returns the model's answer as plain text.
   *
   * @param \Drupal\thunder_ai_prompt_management\AIPromptInterface $prompt
   *   The prompt to run. Supplies the model, and the prompt text unless
   *   $promptText overrides it.
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $entity
   *   The entity to run against, or NULL to run without entity context.
   * @param string|null $promptText
   *   Prompt text overriding the stored one, so callers can run edited but
   *   unsaved text. Defaults to the prompt entity's own text.
   * @param string[] $tags
   *   Operation tags identifying the calling context, for logging/metrics.
   *
   * @return string
   *   The generated text, or an empty string when the provider failed.
   */
  public function run(AIPromptInterface $prompt, ?ContentEntityInterface $entity, ?string $promptText = NULL, array $tags = []): string {
    $response = $this->chat($prompt, $this->systemPrompt($prompt, $entity, $promptText), $tags);

    return $response instanceof ChatMessage ? trim($response->getText()) : '';
  }

  /**
   * Runs a prompt and returns every suggestion the model offered.
   *
   * @param \Drupal\thunder_ai_prompt_management\AIPromptInterface $prompt
   *   The prompt to run.
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $entity
   *   The entity to run against, or NULL to run without entity context.
   * @param string[] $tags
   *   Operation tags identifying the calling context, for logging/metrics.
   *
   * @return string[]
   *   The suggestions, or an empty array when the provider failed.
   */
  public function suggest(AIPromptInterface $prompt, ?ContentEntityInterface $entity, array $tags = []): array {
    $systemPrompt = $this->systemPrompt($prompt, $entity) . "\n\n" . self::SUGGESTIONS_INSTRUCTION;
    $response = $this->chat($prompt, $systemPrompt, $tags);
    if (!$response instanceof ChatMessage) {
      return [];
    }

    // An ignored JSON instruction still yields one usable answer as raw text.
    $decoded = $this->promptJsonDecoder->decode($response);
    if (!is_array($decoded)) {
      $text = trim($response->getText());
      return $text !== '' ? [$text] : [];
    }

    // A single-object answer would fall through array_column() as empty.
    if (array_key_exists('suggestion', $decoded)) {
      $decoded = [$decoded];
    }

    $suggestions = array_map(
      [Html::class, 'decodeEntities'],
      array_filter(array_column($decoded, 'suggestion'), 'is_string')
    );

    return array_values(array_filter(array_map('trim', $suggestions), static fn ($s) => $s !== ''));
  }

  /**
   * Builds the system prompt, with the entity context when one is available.
   */
  private function systemPrompt(AIPromptInterface $prompt, ?ContentEntityInterface $entity, ?string $promptText = NULL): string {
    $promptText ??= (string) $prompt->get('prompt')->value;

    try {
      return $this->promptBuilder->build($promptText, $entity);
    }
    catch (BlueprintException $e) {
      $this->messenger->addWarning($this->t('Could not include the entity context: @message. Running the prompt without it.', [
        '@message' => $e->getMessage(),
      ]));
      return $this->promptBuilder->build($promptText);
    }
  }

  /**
   * Sends one chat request against the prompt's configured model.
   *
   * @param \Drupal\thunder_ai_prompt_management\AIPromptInterface $prompt
   *   The prompt, supplying the configured model.
   * @param string $systemPrompt
   *   The fully built system prompt to send.
   * @param string[] $tags
   *   Operation tags identifying the calling context, for logging/metrics.
   *
   * @return \Drupal\ai\OperationType\Chat\ChatMessage|null
   *   The normalized response, or NULL when the provider failed.
   */
  private function chat(AIPromptInterface $prompt, string $systemPrompt, array $tags = []): ?ChatMessage {
    try {
      $provider = $this->providerPluginManager->getSetProvider('chat', (string) $prompt->get('model')->value);
      // The chat API needs a user turn; instructions live in the system prompt.
      $input = new ChatInput([new ChatMessage('user', 'Please respond.')]);
      $input->setSystemPrompt($systemPrompt);
      $response = $provider['provider_id']->chat($input, $provider['model_id'], [
        'thunder_ai_prompt_management',
        ...$tags,
      ])->getNormalized();

      return $response instanceof ChatMessage ? $response : NULL;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('thunder_ai_prompt_management')->error('The AI provider returned an error: @message', [
        '@message' => $e->getMessage(),
      ]);
      $this->messenger->addError($this->t('The AI provider returned an error: @message', [
        '@message' => $e->getMessage(),
      ]));
      return NULL;
    }
  }

}
