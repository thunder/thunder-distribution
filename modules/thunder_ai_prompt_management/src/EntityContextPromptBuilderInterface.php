<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Builds the system prompt sent when testing an AI prompt.
 */
interface EntityContextPromptBuilderInterface {

  /**
   * Assembles the system prompt, optionally scoped to a real entity.
   *
   * Mirrors what ai_chatbot_assistant_ui_form_bridge sends for an open
   * editor draft: an entity_blueprint schema block (AgentDraftContext) plus
   * the serialized entity (LoadCurrentDraft's function-call result), plus
   * token substitution over the whole prompt - so a prompt author sees the
   * same context the chatbot gives the AI provider.
   *
   * @param string $prompt
   *   The prompt entity's own "prompt" field text.
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $entity
   *   The entity to test against, or NULL to run the prompt text as-is.
   *
   * @return string
   *   The assembled system prompt.
   *
   * @throws \Drupal\entity_blueprint\Exception\BlueprintException
   *   If $entity is given but its bundle's schema can't be built (unknown
   *   bundle, or the current user may not create it).
   */
  public function build(string $prompt, ?ContentEntityInterface $entity = NULL): string;

}
