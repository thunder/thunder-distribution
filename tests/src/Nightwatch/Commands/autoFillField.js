/**
 * @file
 * Automatically fill field with some random data.
 *
 * TODO: We should fill fields with some meaningful data!
 *
 * This provides a custom command, .autoFillField()
 *
 * @param {string} fieldName
 *   The filed name.
 * @param {{type: string}} fieldInfo
 *   The filed information object.
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function autoFillField(fieldName, fieldInfo) {
  const browser = this;

  // eslint-disable-next-line no-console
  console.log(`Auto fill field: ${fieldName}`);

  const fieldIdPart = fieldName.replace(/[_[]/g, "-").replace(/]/g, "");

  switch (fieldInfo.type) {
    // Text field.
    case "string_textfield":
    case "string_textarea": {
      const stringFieldXPath = `//*[starts-with(@id, "edit-${fieldIdPart}-0-value")]`;

      browser.clearValue(stringFieldXPath);
      browser.setValue(
        stringFieldXPath,
        `Some text ${Math.random().toString(36)}`
      );

      return browser;
    }

    case "text_textarea": {
      browser
        .waitForElementVisible(
          `//*[starts-with(@id, "cke_edit-${fieldIdPart}-0-value")]//iframe`,
          10000
        )
        .fillCKEditor(
          `//*[starts-with(@id, "edit-${fieldIdPart}-0-value")]`,
          "Lorem ipsum dolor sit amet, cu choro iudico expetenda qui, sale assum instructior per an. His ne regione oporteat detraxit, integre intellegat definiebas mel id. Mutat persequeris definitiones nec at. Eu est legere facilis partiendo, ad sed sensibus posidonium. Insolens argumentum an pri. Mea at tritani nostrum recteque, et viris interpretaris vis."
        );

      return browser;
    }

    // Number field.
    case "number": {
      const numberFieldXPath = `//*[starts-with(@id, "edit-${fieldIdPart}-0-value")]`;

      browser.clearValue(numberFieldXPath);
      browser.setValue(numberFieldXPath, "1");

      return browser;
    }

    // Select options.
    case "options_select":
      browser.click(`//*[starts-with(@id, "edit-${fieldIdPart}")]/option[2]`);

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

    // Entity browser widget.
    case "entity_browser_entity_reference":
      browser.fillEntityBrowser(
        fieldName,
        fieldInfo.settings.entity_browser,
        fieldInfo.settings.selection_mode
      );

      return browser;

    // Paragraphs widget.
    case "paragraphs":
      browser.paragraphs.autoCreate(fieldName, fieldInfo);

      return browser;

    // Inline entity form.
    case "inline_entity_form_simple":
      if (typeof fieldInfo.inline_entity_form !== "object") {
        browser.perform(() => {
          // eslint-disable-next-line no-console
          console.log(
            "\x1b[31m\x1b[1m%s\x1b[0m",
            `Inline entity form information is not provided for field: ${fieldName}.`
          );
        });

        return browser;
      }

      browser.autoFillFields(
        fieldInfo.inline_entity_form,
        `${fieldName}[0][inline_entity_form]`
      );

      return browser;

    default:
      browser.perform(() => {
        // eslint-disable-next-line no-console
        console.log(
          "\x1b[31m\x1b[1m%s\x1b[0m",
          `Unsupported widget type: ${fieldInfo.type}`
        );
      });
  }

  return browser;
};
