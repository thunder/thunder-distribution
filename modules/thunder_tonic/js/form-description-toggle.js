/* eslint-disable no-bitwise, no-nested-ternary, no-mutable-exports, comma-dangle, strict */

'use strict';

((Drupal) => {
  Drupal.behaviors.formDescriptionToggle = {
    attach: function attach(context) {
      context.querySelectorAll('.help-icon__description-toggle').forEach((elem) => {
        if (elem.dataset.formDescriptionToggleAttached) {
          return;
        }
        elem.dataset.formDescriptionToggleAttached = true;

        elem.addEventListener('click', (event) => {
          event.preventDefault();
          event.currentTarget.closest('.help-icon__description-container').querySelectorAll('.form-item__description, .claro-details__description').forEach((elem) => {
            elem.classList.toggle('visually-hidden');
          });
        });
      });
    }
  };
})(Drupal);
