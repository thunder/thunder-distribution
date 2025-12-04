// modules/thunder_paragraphs/js/paragraphs.click_edit.js
/* eslint-env browser */
// eslint-disable-next-line no-redeclare
/* global Drupal, once */

/**
 * @file
 * Provides configurable click-to-edit functionality for paragraphs.
 *
 * Drupal and once are injected by Drupal core.
 */

(function (Drupal, once) {
  // Default configuration
  const defaults = {
    selector: '.paragraphs-item, .paragraph-form-item--has-preview',
    onceKey: 'thunder-paragraphs-click-edit',
  };

  /**
   * Check if element should have click-to-edit behavior.
   *
   * @param {HTMLElement} wrapper
   *   The paragraph wrapper element.
   * @return {boolean}
   *   TRUE if click-to-edit should be enabled for this element.
   */
  function shouldEnableClickToEdit(wrapper) {
    // Check third-party settings
    const settings = wrapper.dataset.clickEdit;
    if (settings === 'disabled') {
      return false;
    }
    if (settings === 'enabled') {
      return true;
    }

    // Default: check if element matches the pattern for click-to-edit
    return (
      wrapper.classList.contains('paragraphs-item') ||
      wrapper.classList.contains('paragraph-form-item--has-preview')
    );
  }

  /**
   * Find the edit button within a paragraph wrapper.
   *
   * @param {HTMLElement} scope
   *   The scope to search within.
   * @return {HTMLElement|null}
   *   The edit button element or null if not found.
   */
  function findEditButton(scope) {
    return scope.querySelector('.paragraphs-icon-button-edit') ||
      scope.querySelector('.paragraphs-button--edit') ||
      scope.querySelector('.paragraphs-actions [name*="_edit"]') ||
      scope.querySelector('.paragraphs-actions [data-drupal-selector*="edit"]');
  }

  /**
   * Trigger the edit action for a paragraph.
   *
   * @param {HTMLElement} wrapper
   *   The paragraph wrapper element.
   */
  function triggerEdit(wrapper) {
    const button = findEditButton(wrapper);
    if (!button) {
      return;
    }

    // Dispatch mousedown event first (some handlers listen for this)
    button.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
    // Then trigger the actual click
    button.click();
  }

  /**
   * Check if the event target should trigger edit.
   *
   * @param {Event} event
   *   The click event.
   * @return {boolean}
   *   TRUE if edit should be triggered.
   */
  function shouldTriggerEdit(event) {
    // Only handle left mouse button clicks
    if (event.button !== undefined && event.button !== 0) {
      return false;
    }

    const {target} = event;

    // Don't trigger if clicking on form elements, buttons, or links
    if (
      target.matches(
        'input, select, textarea, button, a, [role="button"], .button',
      )
    ) {
      return false;
    }

    // Don't trigger if clicking on delete confirmations or remove buttons
    if (
      target.matches('.paragraphs-features__delete-confirm, [name*="_remove"]')
    ) {
      return false;
    }

    // Don't trigger if clicking within a subform
    if (target.closest('.paragraphs-subform')) {
      return false;
    }

    // Don't trigger if clicking on any element with a click-to-edit-exclude class
    if (target.closest('.click-edit-exclude')) {
      return false;
    }

    return true;
  }

  /**
   * Handle click event on paragraph wrapper.
   *
   * @param {Event} event
   *   The click event.
   */
  function handleParagraphClick(event) {
    if (!shouldTriggerEdit(event)) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    const wrapper = event.currentTarget;
    triggerEdit(wrapper);
  }

  /**
   * Initialize click-to-edit behavior for a paragraph wrapper.
   *
   * @param {HTMLElement} wrapper
   *   The paragraph wrapper element.
   */
  function initClickToEdit(wrapper) {
    if (!shouldEnableClickToEdit(wrapper)) {
      return;
    }

    // Don't initialize if already in edit mode
    if (wrapper.querySelector('.paragraphs-subform')) {
      return;
    }

    // Don't initialize if no edit button is present
    if (!findEditButton(wrapper)) {
      return;
    }

    wrapper.addEventListener('click', handleParagraphClick);
  }

  Drupal.behaviors.thunderParagraphsClickEdit = {
    attach(context, settings) {
      // Get selector from settings or use default
      const selector =
        (settings.thunderParagraphsClickEdit &&
          settings.thunderParagraphsClickEdit.selector) ||
        defaults.selector;

      // Initialize for all matching elements using once()
      once(defaults.onceKey, selector, context).forEach(initClickToEdit);
    },
  };

  // Make utility functions available for testing or other modules
  Drupal.thunderParagraphsClickEdit = {
    defaults,
  };
})(Drupal, once);
