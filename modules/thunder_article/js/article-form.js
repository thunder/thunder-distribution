((Drupal, once) => {
  Drupal.behaviors.disableImplicitSubmission = {
    attach: (context) => {
      once(
        'thunderDisableImplicitSubmission',
        'form.gin-node-edit-form input[type="text"], form.gin-node-edit-form input[type="date"]',
        context,
      ).forEach((element) => {
        element.addEventListener('keydown', (event) => {
          if (event.key === 'Enter') {
            event.preventDefault();
            event.stopPropagation();
          }
        });
      });
    },
  };
})(Drupal, once);
