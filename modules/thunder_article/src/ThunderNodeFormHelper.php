<?php

namespace Drupal\thunder_article;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base for handler for node add/edit forms.
 */
readonly class ThunderNodeFormHelper implements ContainerInjectionInterface {

  /**
   * Constructs a ThunderNodeFormHelper object.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   */
  final public function __construct(protected ThemeManagerInterface $themeManager) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('theme.manager'),
    );
  }

  /**
   * Add library to node form.
   */
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    if (isset($this->getActiveThemes()['gin'])) {
      $form['#attached']['library'][] = 'thunder_article/article-form';
    }
  }

  /**
   * Return current active theme including base themes.
   */
  public function getActiveThemes(): array {
    $activeTheme = $this->themeManager->getActiveTheme();
    $activeThemes = $activeTheme->getBaseThemeExtensions();
    $activeThemes[$activeTheme->getName()] = $activeTheme;

    return $activeThemes;
  }

}
