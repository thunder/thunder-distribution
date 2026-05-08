/**
 * @file
 * Stub replacement for assets.pinterest.com/js/pinit.js used in tests.
 *
 * The real pinit.js replaces each <a data-pin-do="embedPin"> with two nested
 * <span data-pin-id> elements. This stub replicates that structure so tests
 * can assert widget rendering without depending on the external Pinterest CDN.
 */
(function () {
  'use strict';

  function processEmbeds() {
    document.querySelectorAll('a[data-pin-do="embedPin"]').forEach(function (anchor) {
      var match = anchor.href.match(/\/pin\/(\d+)/);
      if (!match) {
        return;
      }
      var pinId = match[1];
      var outer = document.createElement('span');
      outer.setAttribute('data-pin-id', pinId);
      var inner = document.createElement('span');
      inner.setAttribute('data-pin-id', pinId);
      outer.appendChild(inner);
      anchor.parentNode.replaceChild(outer, anchor);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', processEmbeds);
  }
  else {
    processEmbeds();
  }
}());
