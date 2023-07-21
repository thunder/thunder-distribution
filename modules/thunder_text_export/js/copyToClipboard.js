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
      // Change a button value to "Copied" for 2 seconds.
      const button = document.getElementById('edit-copytoclipboard--2--gin-edit-form');

      // Get button width and set it to the button.
      const buttonWidth = button.offsetWidth;
      button.style.width = `${buttonWidth}px`;

      button.value = Drupal.t('Copied ✓');
      button.disabled = true;
      setTimeout(() => {
        button.value = Drupal.t('Text Export');
        button.disabled = false;
      }, 1500);

    } else {
      // eslint-disable-next-line no-alert
      alert(
        'Copied to clipboard requires a secure context (HTTPS served website) and navigator.clipboard support.',
      );
    }
  };
})(Drupal);
