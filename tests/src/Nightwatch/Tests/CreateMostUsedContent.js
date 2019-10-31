/**
 * @file
 * Testing of content creation for most used bundle type.
 */

/**
 * Module "elastic-apm-node" has to be installed for core.
 *
 * You can use Yarn command for that: yarn add elastic-apm-node --dev
 * and it will install that module with it's requirements.
 *
 * We are using "process.cwd()" to get core directory.
 */

// eslint-disable-next-line import/no-dynamic-require
const apm = require(`${process.cwd()}/node_modules/elastic-apm-node`);
const thunderUtils = require("../utils");

module.exports = {
  "@tags": ["Thunder", "Thunder_Base_Set"],
  before(browser, done) {
    browser.apm = apm;

    // Get site information.
    thunderUtils.setSiteInfo(
      "test-admin",
      "test-admin",
      {
        rule: "count",
        index: 0,
        percent_of_instances_threshold: 50
      },
      browser,
      done
    );
  },
  createMostUsedContent(browser) {
    const { bundle, fields } = browser._site_info;

    // We have to filter returned fields, because we always have 100% threshold.
    const fieldsToFill = {
      title: true, // required
      field_2: true, // required
      field_13: {
        bundle_0: {
          field_4: true,
          field_6: true
        },
        bundle_2: {
          field_4: true,
          field_7: true,
          field_12: true
        },
        bundle_4: {
          field_4: true,
          field_12: {
            name: true,
            field_1: true,
            field_2: true
          }
        },
        bundle_8: {
          field_4: true,
          field_7: true
        }
      }, // optional - paragraphs
      field_18: true, // required
      field_23: true // required
    };

    const filteredFields = thunderUtils.filterObject(fields, fieldsToFill);

    browser
      .resizeWindow(1024, 1024)
      .performance.startMeasurement(
        process.env.THUNDER_APM_URL,
        "Create new most used content (min)",
        `.${process.env.THUNDER_SITE_HOSTNAME}`
      )
      .performance.startMark("full task")
      .performance.startMark("login")
      .drupalLogin({ name: "test-admin", password: "test-admin" })
      .performance.endMark()

      .performance.startMark("create new most used content")
      .drupalRelativeURL(`/node/add/${bundle}`)
      // Start using XPATH!!!
      .useXpath()
      .waitForElementVisible('//*[@id="edit-submit"]', 1000)

      // Fill required fields for content bundle.
      .performance.startMark("fill required fields")
      .autoFillFields(filteredFields)
      .performance.endMark();

    // Close mark and save newly created content entity.
    browser.performance
      .endMark()

      // Submit form.
      .click('//*[@id="edit-submit"]')
      .waitForElementVisible(
        '//*[@id="block-thunder-base-page-title"]/div[2]/h1/span',
        60000
      )
      .performance.endMeasurement();

    browser.end();
  }
};
