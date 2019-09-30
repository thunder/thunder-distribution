/**
 * @file
 * Testing of auto-complete field.
 */

// eslint-disable-next-line import/no-dynamic-require
const apm = require(`${process.cwd()}/node_modules/elastic-apm-node`);
const thunderUtils = require("../utils");

module.exports = {
  "@tags": ["Thunder", "Thunder_Base_Set"],
  before(browser, done) {
    browser.apm = apm;

    // Get site information for field with autocomplete field.
    thunderUtils.setSiteInfo(
      "test-admin",
      "test-admin",
      {
        rule: "number_of_fields",
        index: 3
      },
      browser,
      done
    );
  },
  autoCompleteField(browser) {
    const { bundle, required_fields: requiredFields } = browser._site_info;

    browser
      .resizeWindow(1024, 1024)
      .performance.startMeasurement(
        process.env.THUNDER_APM_URL,
        "Auto complete field",
        `.${process.env.THUNDER_SITE_HOSTNAME}`
      )
      .drupalLogin({ name: "test-admin", password: "test-admin" })
      .useXpath();

    browser
      .drupalRelativeURL(`/node/add/${bundle}`)
      .waitForElementVisible('//*[@id="edit-submit"]', 1000);

    // Fill required fields for content bundle.
    browser.performance.startMark("fill required fields");
    const requiredFieldNames = Object.keys(requiredFields);
    requiredFieldNames.forEach(fieldName => {
      // Skip field_22 - because it's used in test.
      if (fieldName === "field_22") {
        return;
      }

      browser.fieldAutoFill(fieldName, requiredFields[fieldName]);
    });
    browser.performance.endMark();

    // TODO - measure time for loading of auto-complete
    // 1. Start performance mark - for finding of existing tag.
    browser.performance.startMark("finding of existing tag");
    // 2. Search for value in "field_22" and select it - tip: select2 commands
    browser.select2.selectValue("field_22", "b", 2, 10000);
    // 3. End performance mark -  for finding of existing tag
    browser.performance.endMark();

    // Submit form.
    browser
      .click('//*[@id="edit-submit"]')
      .waitForElementVisible(
        '//*[@id="block-thunder-base-page-title"]/div[2]/h1/span',
        60000
      )
      .performance.endMeasurement();

    browser.end();
  }
};
