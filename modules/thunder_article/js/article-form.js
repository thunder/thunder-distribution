((Drupal, once) => {
  Drupal.behaviors.myBehavior = {
    attach: (context) => {
      once(
        'thunderEditForm',
        '.region-content form.gin-node-edit-form',
        context,
      ).forEach((element) => {
        element.addEventListener('keydown', (event) => {
          if (event.key === 'Enter') {
            event.preventDefault();
          }
        });
      });
    },
  };
})(Drupal, once);
