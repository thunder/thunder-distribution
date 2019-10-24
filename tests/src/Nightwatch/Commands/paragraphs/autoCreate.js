/**
 * @file
 * Create paragraphs for list of bundles and related fields.
 *
 * This provides a custom command, .paragraphs.autoCreate()
 *
 * @param {string} fieldName
 *   The paragraphs field name.
 * @param {string} paragraphs
 *   The list of paragraph types and their fields.
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function autoCreate(fieldName, paragraphs) {
  const browser = this;

  const { target_type_distribution: paragraphBundles } = paragraphs;
  let position = 1;

  Object.keys(paragraphBundles).forEach(bundleName => {
    const { instances, fields } = paragraphBundles[bundleName];

    for (let i = 0; i < instances; i++) {
      browser.paragraphs.add(fieldName, bundleName, position);
      browser.autoFillFields(fields, `${fieldName}[${position - 1}][subform]`);

      position += 1;
    }
  });

  return browser;
};
