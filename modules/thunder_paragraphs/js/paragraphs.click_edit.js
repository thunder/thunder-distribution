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

/**
 * @typedef {Object} DrupalSettings
 * @property {Object} [thunderParagraphsClickEdit]
 * @property {string} [thunderParagraphsClickEdit.selector]
 */

/* eslint-disable no-multi-assign */
(function thunderParagraphsClickEditBootstrap(Drupal, once) {
  const NS = (Drupal.thunderParagraphsClickEdit =
    Drupal.thunderParagraphsClickEdit || {});
  NS.defaults = {
    selector:
      '.paragraphs-item, .paragraph-form-item--has-preview, [id^="field-paragraphs-"][id*="-item-wrapper"]',
    onceKey: 'thunderParagraphsClickEdit',
  };

  NS.getSelector = function getSelector(settings) {
    if (
      settings &&
      settings.thunderParagraphsClickEdit &&
      settings.thunderParagraphsClickEdit.selector
    ) {
      return settings.thunderParagraphsClickEdit.selector;
    }
    return NS.defaults.selector;
  };

  function findEditButton(scope) {
    return (
      scope.querySelector('.paragraphs-icon-button-edit') ||
      scope.querySelector('.paragraphs-button--edit') ||
      scope.querySelector('.paragraphs-actions [name*="_edit"]') ||
      scope.querySelector('.paragraphs-actions [data-drupal-selector*="edit"]')
    );
  }

  function triggerEdit(wrapper) {
    const btn = findEditButton(wrapper);
    if (!btn) return;
    btn.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
    btn.click();
  }

  function isParagraphWrapper(node) {
    return (
      node.classList.contains('paragraphs-item') ||
      node.querySelector('.paragraphs-actions') !== null ||
      node.querySelector('[data-drupal-selector$="-preview"]') !== null
    );
  }

  function handleParagraphClick(e, wrapper) {
    if (e.button && e.button !== 0) return;
    if (e.target.closest('.paragraphs-subform')) return;
    if (
      e.target.matches('.paragraphs-features__delete-confirm') ||
      e.target.matches('[name*="_remove"]')
    )
      return;
    e.preventDefault();
    triggerEdit(wrapper);
  }

  function bind(wrapper) {
    if (wrapper.dataset.clickEditBound === '1') return;
    if (!isParagraphWrapper(wrapper)) return;
    if (wrapper.querySelector('.paragraphs-subform')) return;
    wrapper.dataset.clickEditBound = '1';
    wrapper.addEventListener(
      'click',
      function paragraphClickListener(e) {
        handleParagraphClick(e, wrapper);
      },
      true,
    );
  }

  function scan(context, selector) {
    once(NS.defaults.onceKey, selector, context).forEach(bind);
  }

  function handleAddedNode(node, selector) {
    if (node.matches && node.matches(selector)) bind(node);
    if (node.querySelectorAll) node.querySelectorAll(selector).forEach(bind);
  }

  function handleMutations(mutations, selector) {
    mutations.forEach(function processMutation(mutation) {
      mutation.addedNodes.forEach(function processAdded(node) {
        if (node.nodeType === 1) handleAddedNode(node, selector);
      });
    });
  }

  function startObserver(selector) {
    if (NS.observer) return;
    NS.observer = new MutationObserver(function mutationCallback(m) {
      handleMutations(m, selector);
    });
    NS.observer.observe(document.documentElement, {
      childList: true,
      subtree: true,
    });
  }

  Drupal.behaviors.thunderParagraphsClickEdit = {
    attach: function attach(context, settings) {
      const selector = NS.getSelector(/** @type {DrupalSettings} */ (settings));
      scan(context, selector);
      startObserver(selector);
    },
  };
})(Drupal, once);
