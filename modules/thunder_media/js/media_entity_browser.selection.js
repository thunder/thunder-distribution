/**
 * @file
 * Defines the behavior of the media entity browser view.
 */

((Drupal, once) => {
  /**
   * Selection of media entities in the entity browser view.
   */
  Drupal.behaviors.mediaEntityBrowserSelection = {
    attach: function (context, settings) {
      // Autoselect is handled by entity_browser.view.js.
      if (settings.entity_browser_widget.auto_select) {
        return;
      }

      once('entityBrowserSelection', 'form.entity-browser-form', context).forEach(
        (element) => {
          element.querySelectorAll('.views-row').forEach(
            (row) => {
              row.addEventListener('click', (event) => {
                event.preventDefault();
                const input = row.querySelector('.views-field-entity-browser-select input.form-checkbox, .views-field-entity-browser-select input.form-radio');
                input.checked = input.checked ? false : 'checked';
                row.classList[input.checked ? 'add' : 'remove']('checked');
              });
            }
          );
        }
      );
    },
  };
})(Drupal, once);
