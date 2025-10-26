(function (Drupal, once) {
  const PARAGRAPH_SELECTOR =
    '.paragraphs-item, .paragraph-form-item--has-preview, [id^="field-paragraphs-"][id*="-item-wrapper"]';

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
      node.querySelector('.paragraphs-actions') ||
      node.querySelector('[data-drupal-selector$="-preview"]')
    );
  }

  function bind(wrapper) {
    if (wrapper.dataset.clickEditBound === '1') return;
    if (!isParagraphWrapper(wrapper)) return;
    if (wrapper.querySelector('.paragraphs-subform')) return;

    wrapper.dataset.clickEditBound = '1';

    wrapper.addEventListener(
      'click',
      (e) => {
        if (e.target.closest('.paragraphs-subform')) return;
        if (
          e.target.matches('.paragraphs-features__delete-confirm') ||
          e.target.matches('[name*="_remove"]')
        ) return;
        e.preventDefault();
        triggerEdit(wrapper);
      },
      true
    );
  }

  function scan(context) {
    once('thunderParagraphsClickEdit', PARAGRAPH_SELECTOR, context).forEach(bind);
  }

  function startObserver() {
    if (window.__thunderParagraphsObserver) return;
    window.__thunderParagraphsObserver = new MutationObserver((muts) => {
      muts.forEach((m) =>
        m.addedNodes.forEach((n) => {
          if (n.nodeType !== 1) return;
          if (n.matches && n.matches(PARAGRAPH_SELECTOR)) bind(n);
          n.querySelectorAll &&
          n.querySelectorAll(PARAGRAPH_SELECTOR).forEach((el) => bind(el));
        })
      );
    });
    window.__thunderParagraphsObserver.observe(document.documentElement, {
      childList: true,
      subtree: true
    });
  }

  Drupal.behaviors.thunderParagraphsClickEdit = {
    attach(context) {
      scan(context);
      startObserver();
    }
  };
})(Drupal, once);
