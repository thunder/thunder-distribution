
((Drupal) => {
  Drupal.behaviors.ginEditForm = {
    attach: (context) => {
      once('ginEditForm', '.region-content form.gin-node-edit-form', context).forEach(form => {
        $(context).on("keydown", ":input:not(textarea)", function(event) {
          if (event.key == "Enter") {
            event.preventDefault();
          }
        });
      });
    }
  };
})(Drupal);
