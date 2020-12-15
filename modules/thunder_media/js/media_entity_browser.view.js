/**
 * @file
 * Defines the behavior of the media entity browser view.
 */

(($) => {
  /**
   * Attaches the behavior of the media entity browser view.
   */
  Drupal.behaviors.mediaEntityBrowserView = {
    attach(context) {
      $(".views-row", context).each(() => {
        const $row = $(this);
        const $input = $row.find(".views-field-entity-browser-select input");

        // When Auto Select functionality is enabled, then select entity
        // on click, without marking it as selected.
        if (drupalSettings.entity_browser_widget.auto_select) {
          $row.once("register-row-click").click((event) => {
            event.preventDefault();

            $row
              .parents("form")
              .find(".entities-list")
              .trigger("add-entities", [[$input.val()]]);
          });
        } else {
          $row[$input.prop("checked") ? "addClass" : "removeClass"]("checked");

          $row.once("register-row-click").click(() => {
            $input.prop("checked", !$input.prop("checked"));
            $row[$input.prop("checked") ? "addClass" : "removeClass"](
              "checked"
            );
          });
        }
      });
    },
  };
})(jQuery, Drupal);
