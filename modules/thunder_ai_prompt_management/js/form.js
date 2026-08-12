/**
 * @file
 * Defines JavaScript behaviors for the ai prompt edit form.
 */

(function ($, Drupal) {
  /**
   * Summaries for the authoring information tab.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches summary behavior for the authoring information details.
   */
  Drupal.behaviors.aiPromptDetailsSummaries = {
    attach(context) {
      const $context = $(context);

      $context.find('.ai-prompt-form-author').drupalSetSummary((context) => {
        const nameElement = context.querySelector('.field--name-uid input');
        const name = nameElement?.value;
        const dateElement = context.querySelector('.field--name-created input');
        const date = dateElement?.value;

        if (name && date) {
          return Drupal.t('By @name on @date', {
            '@name': name,
            '@date': date,
          });
        }
        if (name) {
          return Drupal.t('By @name', {
            '@name': name,
          });
        }
        if (date) {
          return Drupal.t('Authored on @date', {
            '@date': date,
          });
        }
      });
    },
  };
})(jQuery, Drupal);
