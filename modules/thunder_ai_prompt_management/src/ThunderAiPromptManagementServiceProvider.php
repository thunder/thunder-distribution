<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Swaps ai_chatbot_assistant_ui.prompt_source for ThunderPromptSource.
 *
 * Via alter(), not a services.yml override, to avoid a hard dependency.
 */
final class ThunderAiPromptManagementServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if ($container->hasDefinition('ai_chatbot_assistant_ui.prompt_source')) {
      // The definition already carries autowire: true, so re-classing it is
      // enough - ThunderPromptSource's own constructor gets autowired.
      $container->getDefinition('ai_chatbot_assistant_ui.prompt_source')
        ->setClass(ThunderPromptSource::class);
    }
  }

}
