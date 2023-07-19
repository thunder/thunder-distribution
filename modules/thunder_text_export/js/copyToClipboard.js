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
    if (window.isSecureContext && navigator.clipboard) {
      navigator.clipboard.writeText(response.message);
    }
    else {
      alert('Copied to clipboard requires a secure context (HTTPS served website) and navigator.clipboard support.')
    }
  };
})(Drupal);
