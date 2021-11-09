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
  public function getFilters() {
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
  public function getName() {
    return 'thunder_article_filter_extension';
  }

  /**
   * Plains a text. Strips everything evil out.
   *
   * @param array $value
   *   The content to be processed.
   *
   * @return string
   *   The processed content.
   */
  public static function plainText(array $value) {
    $element = \Drupal::service('renderer')->render($value);
    $element = strip_tags($element);
    return html_entity_decode($element, ENT_QUOTES);
  }

  /**
   * Cleans a text and just allow a few tags.
   *
   * @param array $value
   *   The content to be processed.
   *
   * @return string
   *   The processed content.
   */
  public static function basicFormat(array $value) {
    $element = \Drupal::service('renderer')->render($value);
    return strip_tags($element, '<a><em><strong><b><i>');
  }

}
