((Drupal) => {
  /**
   * Command to copy text to clipboard.
   *
   * @param {object} ajax
   *  The ajax object.
   * @param {object} response
   *  The ajax response.
   */
  Drupal.AjaxCommands.prototype.copyToClipboard = (ajax, response) => {
    navigator.clipboard.writeText(response.message);
  };
})(Drupal);
