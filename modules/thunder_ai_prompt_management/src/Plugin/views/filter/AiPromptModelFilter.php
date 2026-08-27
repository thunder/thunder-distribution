<?php

declare(strict_types=1);

namespace Drupal\thunder_ai_prompt_management\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Filters AI prompts by the models actually used on existing prompts.
 */
#[ViewsFilter('ai_prompt_model')]
final class AiPromptModelFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  protected $valueFormType = 'select';

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    #[Autowire(service: 'database')]
    protected readonly Connection $database,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions(): array {
    if ($this->valueOptions === NULL) {
      $models = $this->database->select('ai_prompt_content', 'a')
        ->fields('a', ['model'])
        ->distinct()
        ->orderBy('model')
        ->execute()
        ->fetchCol();
      $this->valueOptions = array_combine($models, $models);
    }
    return $this->valueOptions;
  }

}
