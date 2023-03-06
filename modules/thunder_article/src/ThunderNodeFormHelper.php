<?php

namespace Drupal\thunder_article;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

/**
 * Base for handler for node add/edit forms.
 */
class ThunderNodeFormHelper implements ContainerInjectionInterface {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected ThemeManagerInterface $themeManager;

  /**
   * Constructs a ThunderNodeFormHelper object.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  final public function __construct(ThemeManagerInterface $theme_manager) {
    $this->themeManager = $theme_manager;
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
