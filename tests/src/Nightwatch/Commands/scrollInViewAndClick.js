/**
 * @file
 * Helper function to scroll element in center of page and click it.
 *
 * This provides a custom command, .scrollInViewAndClick()
 *
 * @param {string} selector
 *   The XPATH selector for the element.
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function scrollInViewAndClick(selector) {
  const browser = this;

  browser.scrollIntoMiddleOfView(selector).click(selector);

  return browser;
};
