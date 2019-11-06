/**
 * @file
 * Create empty paragraph for defined paragraph type.
 *
 * This provides a custom command, .paragraphs.add()
 *
 * @param {string} fieldName
 *   The paragraphs field name.
 * @param {string} type
 *   The paragraphs type.
 * @param {int} position
 *   The position where paragraph should be added.
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function add(fieldName, type, position) {
  const browser = this;

  if (position < 1) {
    browser.perform(() => {
      // eslint-disable-next-line no-console
      console.log(
        "\x1b[31m\x1b[1m%s\x1b[0m",
        `Paragraph position has to be 1 or bigger. Following value is provided: ${position}.`
      );
    });

    return browser;
  }

  const fieldNameId = fieldName.replace(/_/g, "-");
  const addButtonPosition = position * 2 - 1;

  browser
    .scrollIntoMiddleOfView(
      `//table[contains(@id, "${fieldNameId}-values")]/tbody/tr[${addButtonPosition}]//input`
    )
    .click(
      `//table[contains(@id, "${fieldNameId}-values")]/tbody/tr[${addButtonPosition}]//input`
    )
    .click(`//*[@name="${fieldName}_${type}_add_more"]`)
    .waitForElementVisible(
      `//table[contains(@id, "${fieldNameId}-values")]/tbody/tr[${addButtonPosition +
        1}]//div[contains(@class, "ajax-new-content")]`,
      10000
    );

  return browser;
};
