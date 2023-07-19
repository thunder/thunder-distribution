(function ($, Drupal) {

  /**
   * Add new custom command.
   */
  Drupal.AjaxCommands.prototype.copyToClipboard = function (ajax, response, status) {
    navigator.clipboard.writeText(response.message);
  }

})(jQuery, Drupal);
