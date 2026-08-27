/**
 * @file
 * Defines JavaScript behaviors for the ai prompt edit form.
 */

(($, Drupal) => {
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

      $context.find('.ai-prompt-form-author').drupalSetSummary((element) => {
        const nameElement = element.querySelector('.field--name-uid input');
        const name = nameElement && nameElement.value;
        const dateElement = element.querySelector('.field--name-created input');
        const date = dateElement && dateElement.value;

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

((Drupal, once) => {
  /**
   * Mirrors the real submit buttons' disabled/loading state onto Gin's
   * sticky-header button clones, which sit outside the AJAX progress
   * indicator's reach (the real #actions container is squeezed to a
   * 1x1px fixed box once Gin's sticky actions take over).
   *
   * The indicator is a real sibling element, not a ::after on the sticky
   * button: browsers never paint generated content on <input> elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.aiPromptTestFormStickyProgress = {
    attach(context) {
      once('ai-prompt-test-form-sticky-progress', '.ai-prompt-test-form [data-drupal-selector="edit-actions"]', context).forEach((actions) => {
        actions.querySelectorAll('[data-drupal-selector]').forEach((button) => {
          const sticky = document.querySelector(`[data-drupal-selector="gin-sticky-${button.dataset.drupalSelector}"]`);
          if (!sticky) {
            return;
          }
          const container = sticky.closest('.gin-sticky-form-actions');
          let spinner = container?.querySelector(':scope > .ai-prompt-test-form-spinner');
          if (container && !spinner) {
            spinner = document.createElement('span');
            spinner.className = 'ai-prompt-test-form-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            container.appendChild(spinner);
          }
          const observer = new MutationObserver(() => {
            sticky.disabled = button.disabled;
            spinner?.classList.toggle('is-active', button.disabled);
          });
          observer.observe(button, { attributes: true, attributeFilter: ['disabled'] });
        });
      });
    },
  };
})(Drupal, once);
