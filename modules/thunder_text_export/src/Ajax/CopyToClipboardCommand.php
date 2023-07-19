<?php

namespace Drupal\thunder_text_export\Ajax;

use Drupal\Core\Ajax\CommandInterface;

class CopyToClipboardCommand implements CommandInterface {

  /**
   * The text to be copied to the clipboard.
   *
   * @var string
   */
  protected $text;

  /**
   * Constructs an ClipboardCommand object.
   *
   * @param string $text
   *   The text to be copied to the clipboard.
   */
  public function __construct($text) {
    $this->text = $text;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'copyToClipboard',
      'message' => $this->text,
    ];
  }

}
