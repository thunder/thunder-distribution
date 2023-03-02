(($, Drupal, once) => {
  Drupal.behaviors.myBehavior = {
    attach: (context) => {
      once('thunderEditForm', '.region-content form.gin-node-edit-form', context).forEach(
        (element) => {
          $(element).on('keydown', ':input:not(textarea)', (event) => {
            if (event.key === 'Enter') {
              event.preventDefault();
            }
          });
        }
      );
    }
  };
})(jQuery, Drupal, once);
