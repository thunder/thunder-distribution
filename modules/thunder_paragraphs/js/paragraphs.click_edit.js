/**
 * @file
 * Provides click-to-edit functionality for paragraphs.
 */

(function paragraphsClickEditModule(Drupal, once) {
  const PARAGRAPH_SELECTOR =
    '.paragraphs-item, .paragraph-form-item--has-preview, [id^="field-paragraphs-"][id*="-item-wrapper"]';

  /**
   * Finds the edit button within a paragraph wrapper.
   *
   * @param {HTMLElement} scope
   *   The scope to search within.
   *
   * @return {HTMLElement|null}
   *   The edit button element or null.
   */
  function findEditButton(scope) {
    return (
      scope.querySelector('.paragraphs-icon-button-edit') ||
      scope.querySelector('.paragraphs-button--edit') ||
      scope.querySelector('.paragraphs-actions [name*="_edit"]') ||
      scope.querySelector('.paragraphs-actions [data-drupal-selector*="edit"]')
    );
  }

  /**
   * Triggers the edit action on a paragraph wrapper.
   *
   * @param {HTMLElement} wrapper
   *   The paragraph wrapper element.
   */
  function triggerEdit(wrapper) {
    const btn = findEditButton(wrapper);
    if (!btn) {
      return;
    }
    btn.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
    btn.click();
  }

  /**
   * Checks if a node is a paragraph wrapper.
   *
   * @param {HTMLElement} node
   *   The node to check.
   *
   * @return {boolean}
   *   True if the node is a paragraph wrapper.
   */
  function isParagraphWrapper(node) {
    return (
      node.classList.contains('paragraphs-item') ||
      node.querySelector('.paragraphs-actions') !== null ||
      node.querySelector('[data-drupal-selector$="-preview"]') !== null
    );
  }

  /**
   * Handles click events on paragraph wrappers.
   *
   * @param {Event} e
   *   The click event.
   * @param {HTMLElement} wrapper
   *   The paragraph wrapper element.
   */
  function handleParagraphClick(e, wrapper) {
    // Ignore clicks within subforms.
    if (e.target.closest('.paragraphs-subform')) {
      return;
    }
    // Ignore clicks on delete confirm or remove buttons.
    if (
      e.target.matches('.paragraphs-features__delete-confirm') ||
      e.target.matches('[name*="_remove"]')
    ) {
      return;
    }
    e.preventDefault();
    triggerEdit(wrapper);
  }

  /**
   * Binds click-to-edit functionality to a paragraph wrapper.
   *
   * @param {HTMLElement} wrapper
   *   The paragraph wrapper element.
   */
  function bind(wrapper) {
    // Skip if already bound.
    if (wrapper.dataset.clickEditBound === '1') {
      return;
    }
    // Skip if not a paragraph wrapper.
    if (!isParagraphWrapper(wrapper)) {
      return;
    }
    // Skip if already in edit mode.
    if (wrapper.querySelector('.paragraphs-subform')) {
      return;
    }

    wrapper.dataset.clickEditBound = '1';

    wrapper.addEventListener(
      'click',
      function clickHandler(e) {
        handleParagraphClick(e, wrapper);
      },
      true,
    );
  }

  /**
   * Scans the context for paragraph elements and binds click handlers.
   *
   * @param {HTMLElement|Document} context
   *   The context to scan.
   */
  function scan(context) {
    once('thunderParagraphsClickEdit', PARAGRAPH_SELECTOR, context).forEach(
      bind,
    );
  }

  /**
   * Handles a newly added node from MutationObserver.
   *
   * @param {Node} node
   *   The added node.
   */
  function handleAddedNode(node) {
    if (node.matches && node.matches(PARAGRAPH_SELECTOR)) {
      bind(node);
    }
    if (node.querySelectorAll) {
      node.querySelectorAll(PARAGRAPH_SELECTOR).forEach(bind);
    }
  }

  /**
   * Processes mutations from MutationObserver.
   *
   * @param {MutationRecord[]} mutations
   *   Array of mutation records.
   */
  function handleMutations(mutations) {
    mutations.forEach(function mutationHandler(mutation) {
      mutation.addedNodes.forEach(function nodeHandler(node) {
        if (node.nodeType === 1) {
          handleAddedNode(node);
        }
      });
    });
  }

  /**
   * Starts the MutationObserver to watch for new paragraph elements.
   */
  function startObserver() {
    if (window.__thunderParagraphsObserver) {
      return;
    }

    window.__thunderParagraphsObserver = new MutationObserver(handleMutations);

    window.__thunderParagraphsObserver.observe(document.documentElement, {
      childList: true,
      subtree: true,
    });
  }

  /**
   * Drupal behavior for paragraph click-to-edit functionality.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.thunderParagraphsClickEdit = {
    attach(context) {
      scan(context);
      startObserver();
    },
  };
})(Drupal, once);
