/**
 * @file
 * Provides configurable click-to-edit functionality for paragraphs.
 */
(function (Drupal, once) {
  const NS = (Drupal.thunderParagraphsClickEdit = Drupal.thunderParagraphsClickEdit || {});
  NS.defaults = {
    selector: '.paragraphs-item, .paragraph-form-item--has-preview, [id^="field-paragraphs-"][id*="-item-wrapper"]',
    onceKey: 'thunderParagraphsClickEdit'
  };

  NS.getSelector = function (settings) {
    return (settings?.thunderParagraphsClickEdit?.selector || NS.defaults.selector);
  };

  /**
   * Find the edit button within the given scope.
   *
   * @param {Element} scope
   *  The scope to search within.
   *
   * @return {Element|null}
   * The edit button element, or null if not found.
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
   * Trigger the edit action on the paragraph wrapper.
   *
   * @param wrapper {HTMLElement} Wrapper element of the paragraph.
   *   the paragraph element.
   */
  function triggerEdit(wrapper) {
    const btn = findEditButton(wrapper);
    if (!btn) return;
    btn.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
    btn.click();
  }

  /**
   * Check if the given node is a paragraph wrapper.
   *
   * @param {HTMLElement} node
   *  The node to check.
   *
   * @returns {boolean|boolean}
   *  True if the node is a paragraph wrapper, false otherwise.
   */
  function isParagraphWrapper(node) {
    return (
      node.classList.contains('paragraphs-item') ||
      node.querySelector('.paragraphs-actions') !== null ||
      node.querySelector('[data-drupal-selector$="-preview"]') !== null
    );
  }
  /**
   * Handle click events on paragraph wrappers.
   *
   * @param {Event} e
   * The click event.
   * @param {HTMLElement} wrapper
   *   The paragraph wrapper element.
   * @returns {void}
   */
  function handleParagraphClick(e, wrapper) {
    if (e.target.closest('.paragraphs-subform')) return;
    if (e.target.matches('.paragraphs-features__delete-confirm') || e.target.matches('[name*="_remove"]')) return;
    e.preventDefault();
    triggerEdit(wrapper);
  }
  /**
   * Bind click event listener to the paragraph wrapper.
   *
   * @param {HTMLElement} wrapper
   *   The paragraph wrapper element.
   */
  function bind(wrapper) {
    if (wrapper.dataset.clickEditBound === '1') return;
    if (!isParagraphWrapper(wrapper)) return;
    if (wrapper.querySelector('.paragraphs-subform')) return;
    wrapper.dataset.clickEditBound = '1';
    wrapper.addEventListener('click', (e) => handleParagraphClick(e, wrapper), true);
  }

  /**
   * Scan the context for paragraph wrappers and bind click event listeners.
   * @param context
   * @param selector
   */
  function scan(context, selector) {
    once(NS.defaults.onceKey, selector, context).forEach(bind);
  }
  /**
   * Handle added nodes in mutations.
   * @param node
   * @param selector
   */
  function handleAddedNode(node, selector) {
    if (node.matches && node.matches(selector)) bind(node);
    if (node.querySelectorAll) node.querySelectorAll(selector).forEach(bind);
  }

  /**
   * Handle mutations from the MutationObserver.
   *
   * @param mutations
   * @param selector
   */
  function handleMutations(mutations, selector) {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === 1) handleAddedNode(node, selector);
      });
    });
  }

  /**
   * Start the MutationObserver to monitor for added paragraph wrappers.
   * @param selector
   */
  function startObserver(selector) {
    if (NS.observer) return;
    NS.observer = new MutationObserver((m) => handleMutations(m, selector));
    NS.observer.observe(document.documentElement, { childList: true, subtree: true });
  }

  Drupal.behaviors.thunderParagraphsClickEdit = {
    attach(context, settings) {
      const selector = NS.getSelector(settings);
      scan(context, selector);
      startObserver(selector);
    }
  };
})(Drupal, once);
