// javascript
/* eslint-env browser */
(function (Drupal, once) {
  const defaults = {
    selector: '.paragraphs-item, .paragraph-form-item--has-preview',
    onceKey: 'thunder-paragraphs-click-edit',
    enabled: true,
  };

  function shouldEnableClickToEdit(wrapper) {
    const settings = wrapper.dataset.clickEdit;
    if (settings === 'disabled') {
      return false;
    }
    if (settings === 'enabled') {
      return true;
    }
    return (
      wrapper.classList.contains('paragraphs-item') ||
      wrapper.classList.contains('paragraph-form-item--has-preview')
    );
  }

  function findEditButton(scope) {
    return (
      scope.querySelector('.paragraphs-icon-button-edit') ||
      scope.querySelector('.paragraphs-button--edit') ||
      scope.querySelector('.paragraphs-actions [name*="_edit"]') ||
      scope.querySelector('.paragraphs-actions [data-drupal-selector*="edit"]')
    );
  }

  function triggerEdit(wrapper) {
    const button = findEditButton(wrapper);
    if (!button) {
      return;
    }
    button.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
    button.click();
  }

  function shouldTriggerEdit(event) {
    if (event.button !== undefined && event.button !== 0) {
      return false;
    }
    const { target } = event;
    if (
      target.matches(
        'input, select, textarea, button, a, [role="button"], .button',
      )
    ) {
      return false;
    }
    if (
      target.matches('.paragraphs-features__delete-confirm, [name*="_remove"]')
    ) {
      return false;
    }
    if (target.closest('.paragraphs-subform')) {
      return false;
    }
    if (target.closest('.click-edit-exclude')) {
      return false;
    }
    return true;
  }

  function handleParagraphClick(event) {
    if (!shouldTriggerEdit(event)) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
    const wrapper = event.currentTarget;
    triggerEdit(wrapper);
  }

  function initClickToEdit(wrapper) {
    if (!shouldEnableClickToEdit(wrapper)) {
      return;
    }
    if (wrapper.querySelector('.paragraphs-subform')) {
      return;
    }
    if (!findEditButton(wrapper)) {
      return;
    }
    wrapper.addEventListener('click', handleParagraphClick);
  }

  Drupal.behaviors.thunderParagraphsClickEdit = {
    attach(context, settings) {
      const behaviorSettings = settings.thunderParagraphsClickEdit || {};
      const enabled =
        typeof behaviorSettings.enabled === 'boolean'
          ? behaviorSettings.enabled
          : defaults.enabled;

      if (!enabled) {
        return;
      }

      const selector = behaviorSettings.selector || defaults.selector;
      once(defaults.onceKey, selector, context).forEach(initClickToEdit);
    },
  };

  Drupal.thunderParagraphsClickEdit = {
    defaults,
  };
})(Drupal, once);
