<?php

namespace Drupal\thunder_article\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Introduce some twig filters.
 */
class FilterExtension extends AbstractExtension {

  /**
   * Returns introduced filters.
   *
   * @return array
   *   Declared Twig filters
   */
  public function getFilters(): array {
    return [
      new TwigFilter('plain_text', [$this, 'plainText']),
      new TwigFilter('basic_format', [$this, 'basicFormat'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Returns the name of the extension.
   *
   * @return string
   *   The extension name
   */
  public function getName(): string {
    return 'thunder_article_filter_extension';
  }

  /**
   * Plains a text. Strips everything evil out.
   *
   * @param array|string|null $value
   *   The content to be processed.
   *
   * @return string
   *   The processed content.
   */
  public static function plainText($value): string {
    $element = self::render($value);
    $element = strip_tags((string) $element);
    return html_entity_decode($element, ENT_QUOTES);
  }

  /**
   * Cleans a text and just allow a few tags.
   *
   * @param array|string|null $value
   *   The content to be processed.
   *
   * @return string
   *   The processed content.
   */
  public static function basicFormat($value): string {
    $element = self::render($value);
    return strip_tags((string) $element, '<a><em><strong><b><i>');
  }

  /**
   * Drop-in replacement for deprecated render() function.
   *
   * \Drupal::service('renderer')->render() is not a fully compatible
   * replacement of render(). It does not handle the input values that are not
   * render arrays in the same way.
   *
   * @param mixed $element
   *   The render element.
   *
   * @phpstan-ignore-next-line
   */
  private static function render(&$element) {
    if (!$element && $element !== 0) {
      return NULL;
    }
    if (is_array($element)) {
      // Early return if this element was pre-rendered (no need to re-render).
      if (isset($element['#printed']) && $element['#printed'] == TRUE && isset($element['#markup']) && strlen($element['#markup']) > 0) {
        return $element['#markup'];
      }
      show($element);
      return \Drupal::service('renderer')->render($element);
    }
    else {
      // Safe-guard for inappropriate use of render() on flat variables: return
      // the variable as-is.
      return $element;
    }
  }

}
