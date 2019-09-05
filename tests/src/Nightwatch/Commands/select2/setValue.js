/**
 * @file
 * Sets tag for element. It can be existing tag or new one.
 *
 * This provides a custom command, .select2.setValue()
 *
 * @param {string} field
 *   The element selector.
 * @param {string} name
 *   The tag name.
 * @param {string} waitFor
 *   The element selector that should be waited for.
 * @param {int} wait
 *   The wait time in milliseconds. Default: 5000
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function setValue(field, name, waitFor, wait = 5000) {
  const browser = this;

  browser
    .setValue(field, name)
    .waitForElementVisible(waitFor, wait)
    .keys([browser.Keys.ENTER]);

  return browser;
};
