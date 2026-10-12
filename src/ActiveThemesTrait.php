<?php

namespace Drupal\thunder;

/**
 * Provides access to the active theme and its base themes.
 *
 * Classes using this trait must provide the theme manager in a $themeManager
 * property.
 */
trait ActiveThemesTrait {

  /**
   * Return current active theme including base themes.
   *
   * @return \Drupal\Core\Theme\ActiveTheme[]|\Drupal\Core\Extension\Extension[]
   *   The base theme extensions and the active theme, keyed by machine name.
   */
  public function getActiveThemes(): array {
    $activeTheme = $this->themeManager->getActiveTheme();
    $activeThemes = $activeTheme->getBaseThemeExtensions();
    $activeThemes[$activeTheme->getName()] = $activeTheme;

    return $activeThemes;
  }

}
