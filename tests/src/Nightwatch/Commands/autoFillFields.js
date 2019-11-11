/**
 * @file
 * Automatically fill multiple fields with some random data.
 *
 * This provides a custom command, .autoFillFields()
 *
 * @param {{type: string}[]} fields
 *   The field information objects.
 * @param {string} parent
 *   The parent path. Fe. "field_paragraphs[0][subform]"
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function autoFillFields(fields, parent = "") {
  const browser = this;
  const fieldNames = Object.keys(fields);

  fieldNames.forEach(fieldName => {
    browser.autoFillField(
      parent.length === 0 ? fieldName : `${parent}[${fieldName}]`,
      fields[fieldName]
    );
  });

  return browser;
};
