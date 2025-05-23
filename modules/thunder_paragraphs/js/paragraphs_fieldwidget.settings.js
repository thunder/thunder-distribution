/**
 * @file
 * Show warnings on paragraphs widget third party settings.
 */
((Drupal, once) => {
  /**
   * Theme function displaying a warning.
   *
   * @param {object} options
   *   Additional data
   @param {string} [options.name]
   *   The name of the setting.
   *
   * @return {string}
   *   Returns markup.
   */
  Drupal.theme.thunderParagraphsFieldWidgetSettingsWarning = (options) => {
    const message = Drupal.t(
      'The !option option is not supported for the Thunder distribution because of potential data loss in combination with the inline_entity_form module. If you want to use it, make sure to remove all inline entity forms from your paragraph types.',
      { '!option': options.name },
    );
    return Drupal.theme('message', { text: message }, { type: 'warning' });
  };

  /**
   * Display warning message for certain paragraphs field widget settings.
   */
  Drupal.behaviors.thunderParagraphsFieldWidgetSettings = {
    attach: function attach(context) {
      const form = once(
        'paragraphsFieldWidgetSettings',
        '[data-drupal-selector="edit-fields-field-paragraphs-settings-edit-form"]',
        context,
      ).shift();

      if (!form) {
        return;
      }
      // Autocollapse
      let elem = form.querySelector(
        '[data-drupal-selector="edit-fields-field-paragraphs-settings-edit-form-settings-autocollapse"]',
      );
      let message = Drupal.theme(
        'thunderParagraphsFieldWidgetSettingsWarning',
        { name: Drupal.t('Autocollapse') },
      );
      elem.closest('.form-item').appendChild(message);

      // Collapse / Edit all
      elem = form.querySelector(
        '[data-drupal-selector="edit-fields-field-paragraphs-settings-edit-form-settings-features-collapse-edit-all"]',
      );
      message = Drupal.theme('thunderParagraphsFieldWidgetSettingsWarning', {
        name: Drupal.t('Collapse / Edit all'),
      });
      elem.closest('.fieldset__wrapper').appendChild(message);
    },
  };
})(Drupal, once);
