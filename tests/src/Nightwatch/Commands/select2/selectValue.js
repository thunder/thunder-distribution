/**
 * @file
 * Select an element after search.
 *
 * This provides a custom command, .select2.selectValue()
 *
 * @param {string} field
 *   The field name.
 * @param {string} search
 *   The element to search for.
 * @param {int} index
 *   The index to select from list after search.
 * @param {int} searchWait
 *   The wait time for search result in milliseconds. Default: 5000
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function selectValue(
  field,
  search,
  index,
  searchWait = 5000
) {
  const browser = this;
  const fieldIdPart = field.replace(/_/g, "-");

  browser
    .setValue(`//*[@id="edit-${fieldIdPart}-wrapper"]//input`, search)
    .waitForElementVisible(
      `//*[@id="select2-edit-${fieldIdPart}-results"]/li[${index}]`,
      searchWait
    )
    .click(`//*[@id="select2-edit-${fieldIdPart}-results"]/li[${index}]`);

  return browser;
};
