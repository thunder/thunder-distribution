// javascript
/* eslint-env browser */
(function thunderParagraphsClickEditIife(Drupal, once) {
  const defaults = {
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
  }

  function shouldTriggerEdit(event) {
    const { button, target } = event;
    return (
      (button === undefined || button === 0) &&
      !target.matches('input, select, textarea, button, a, [role="button"], .button') &&
      !target.matches('.paragraphs-features__delete-confirm, [name*="_remove"]') &&
      !target.closest('.paragraphs-subform') &&
      !target.closest('.click-edit-exclude')
    );
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
    if (
      !shouldEnableClickToEdit(wrapper) ||
      wrapper.querySelector('.paragraphs-subform') ||
      !findEditButton(wrapper)
    ) {
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

      if (enabled) {
        const selector =
          'tr.draggable .paragraphs-item, .paragraph-form-item--has-preview';
        once(defaults.onceKey, selector, context).forEach(initClickToEdit);
      }
    },
  };

  Drupal.thunderParagraphsClickEdit = {
    defaults,
  };
})(Drupal, once);
