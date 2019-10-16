/**
 * @file
 * Automatically fill multiple fields with some random data.
 *
 * This provides a custom command, .autoFillFields()
 *
 * @param {{type: string}[]} fields
 *   The field information objects.
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function autoFillFields(fields) {
  const browser = this;
  const fieldNames = Object.keys(fields);

  fieldNames.forEach(fieldName => {
    browser.autoFillField(fieldName, fields[fieldName]);
  });

  return browser;
};
