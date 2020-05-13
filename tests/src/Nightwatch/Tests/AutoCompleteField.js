/**
 * @file
 * Testing of auto-complete field.
 */

// eslint-disable-next-line import/no-dynamic-require
const apm = require(`${process.cwd()}/node_modules/elastic-apm-node`);
const thunderUtils = require("../utils");

module.exports = {
  "@tags": ["Thunder_disabled", "Thunder_Base_Set_disabled"],
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
    const { bundle, fields } = browser._site_info;

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
    // TODO: This part should be removed, because we are not submitting form.
    const requiredFieldNames = Object.keys(fields);
    requiredFieldNames.forEach(fieldName => {
      // Skip field_22 - because it's used in test.
      if (fieldName === "field_22") {
        return;
      }
      browser.autoFillField(fieldName, fields[fieldName]);
    });

    browser.performance
      .startMark("Select a first value")
      .select2.selectValue("field_22", "bund", 2, 10000)
      .performance.endMark();

    browser.performance
      .startMark("Select a second value")
      .select2.selectValue("field_22", "bund", 4, 10000)
      .performance.endMark();

    browser.performance.endMeasurement();
    browser.end();
  }
};
