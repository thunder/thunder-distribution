/**
 * @file
 * Automatically fill field with some random data.
 *
 * TODO: We should fill fields with some meaningful data!
 *
 * This provides a custom command, .fieldAutoFill()
 *
 * @param {string} fieldName
 *   The filed name.
 * @param {{type: string}} fieldInfo
 *   The filed information object.
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function fieldAutoFill(fieldName, fieldInfo) {
  const browser = this;

  // eslint-disable-next-line no-console
  console.log("Auto fill field: ", fieldName);

  const fieldIdPart = fieldName.replace(/_/g, "-");

  switch (fieldInfo.type) {
    // Text field.
    case "string_textfield":
    case "string_textarea":
      browser.clearValue(`//*[@id="edit-${fieldIdPart}-0-value"]`);
      browser.setValue(
        `//*[@id="edit-${fieldIdPart}-0-value"]`,
        `Some text ${Math.random().toString(36)}`
      );

      return browser;

    // Number field.
    case "number":
      browser.clearValue(`//*[@id="edit-${fieldIdPart}-0-value"]`);
      browser.setValue(`//*[@id="edit-${fieldIdPart}-0-value"]`, "1");

      return browser;

    // Select options.
    case "options_select":
      browser.click(`//*[@id="edit-${fieldIdPart}"]/option[2]`);

      return browser;

    // Default Auto-Complete widget.
    case "entity_reference_autocomplete":
      browser
        .clearValue(`//*[@id="edit-${fieldIdPart}-0-target-id"]`)
        .setValue(`//*[@id="edit-${fieldIdPart}-0-target-id"]`, "b")
        .waitForElementVisible('//*[@id="ui-id-1"]', 10000)
        .click('//*[@id="ui-id-1"]/*[1]/a');

      return browser;

    // Select2 Auto-Complete widget.
    case "select2_entity_reference":
      browser.select2.selectValue(fieldName, "b", 1, 10000);

      return browser;

    default:
      // eslint-disable-next-line no-console
      console.log("Unsupported widget type: ", fieldInfo.type);

      // If we do not make any action in command, for some reason test hangs!
      browser.pause(1);
  }

  return browser;
};
