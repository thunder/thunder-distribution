/**
 * @file
 * Testing of editing for most used bundle type.
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
      },
      browser,
      done
    );
  },
  editMostUsedContent(browser) {
    const { bundle, fields } = browser._site_info;

    browser
      .resizeWindow(1024, 1024)
      .performance.startMeasurement(
        process.env.THUNDER_APM_URL,
        "Edit most used content (min)",
        `.${process.env.THUNDER_SITE_HOSTNAME}`
      )
      .drupalLogin({ name: "test-admin", password: "test-admin" })
      .useXpath()
      .drupalRelativeURL(`/admin/content?type=${bundle}`)

      .performance.startMark("full task")
      .performance.startMark("load form")
      .click(
        '(//li[contains(@class,"dropbutton-action")])[1]//a[contains(@href, "edit")]'
      )
      .waitForElementVisible('//*[@id="edit-submit"]', 1000)
      .performance.endMark() // "load form" task.

      .autoFillFields(fields)

      .performance.startMark("submit save form")
      .click('//*[@id="edit-submit"]')
      .waitForElementVisible('//*[@id="block-thunder-admin-content"]', 10000)
      .performance.endMark() // "submit save form" task.

      .performance.endMark() // "full task" task.

      .performance.endMeasurement();

    browser.end();
  },
};
