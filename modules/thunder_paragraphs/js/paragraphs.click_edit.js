// javascript
/* eslint-env browser */
((Drupal, once) => {
  // --- Namespace object (overrideable) ---
  const clickEdit = Drupal.thunderParagraphsClickEdit || {};

  clickEdit.findEditButton = (scope) => {
    return (
      scope.querySelector('.paragraphs-icon-button-edit') ||
      scope.querySelector('.paragraphs-button--edit') ||
      scope.querySelector('.paragraphs-actions [name*="_edit"]') ||
      scope.querySelector('.paragraphs-actions [data-drupal-selector*="edit"]')
    );
  };

  clickEdit.triggerEdit = (wrapper) => {
    const button = clickEdit.findEditButton(wrapper);
    if (!button) {
      return;
    }
    button.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
  };

  clickEdit.shouldTriggerEdit = (event) => {
    const { button, target } = event;
    return (
      (button === undefined || button === 0) &&
      !target.matches(
        'input, select, textarea, button, a, [role="button"], .button',
      ) &&
      !target.matches(
        '.paragraphs-features__delete-confirm, [name*="_remove"]',
      ) &&
      !target.closest('.click-edit-exclude')
    );
  };

  clickEdit.handleParagraphClick = (event) => {
    if (!clickEdit.shouldTriggerEdit(event)) return;

    clickEdit.triggerEdit(event.currentTarget);
    event.preventDefault();
  };

  Drupal.behaviors.thunderParagraphsClickEdit = {
    attach(context, settings) {
      if (
        !settings.thunderParagraphsClickEdit ||
        !settings.thunderParagraphsClickEdit.enabled
      )
        return;

      const rows = Array.from(
        context.querySelectorAll('.paragraph-form-item--has-preview'),
      )
        .map((wrapper) => wrapper.closest('tr'))
        .filter((tr) => tr);

      once('thunder-paragraphs-click-edit', rows, context).forEach((elem) => {
        if (!clickEdit.findEditButton(elem)) return;
        elem.addEventListener('click', clickEdit.handleParagraphClick);
      });
    },
  };

  // Expose namespace for overrides.
  Drupal.thunderParagraphsClickEdit = clickEdit;
})(Drupal, once);
