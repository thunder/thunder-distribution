/**
 * @file
 * Testing of content overview filtering.
 */

// eslint-disable-next-line import/no-dynamic-require
const apm = require(`${process.cwd()}/node_modules/elastic-apm-node`);

module.exports = {
  "@tags": ["Thunder", "Thunder_Base_Set"],
  before(browser, done) {
    browser.apm = apm;

    done();
  },
  contentOverviewFiltering(browser) {
    browser
      .resizeWindow(1024, 1024)
      .performance.startMeasurement(
        process.env.THUNDER_APM_URL,
        "Filtering of content overview",
        `.${process.env.THUNDER_SITE_HOSTNAME}`
      )
      .performance.startMark("full task")
      .performance.startMark("login")
      .drupalLogin({ name: "test-admin", password: "test-admin" })
      // end "login"
      .performance.endMark()
      .performance.startMark("Open content overview page")
      .drupalRelativeURL("/admin/content_bundle_0")
      .useXpath()
      .moveToElement(
        '//*[@id="block-thunder-admin-content"]/div/div/nav/ul/li[1]',
        10,
        10
      )
      .waitForElementPresent(
        '//*[@id="views-form-content-bundle-0-page-1"]/table[2]/tbody/tr[1]'
      )
      // end "Open content overview page"
      .performance.endMark()
      .performance.startMark("Filter by type")
      .setValue('//*[@id="edit-type"]', "bundle_6")
      .click('//*[@id="edit-submit-content-bundle-0"]')
      .pause(10000)
      .moveToElement(
        '//*[@id="block-thunder-admin-content"]/div/div/nav/ul/li[1]',
        10,
        10
      )
      .waitForElementPresent(
        '//*[@id="views-form-content-bundle-0-page-1"]/table[2]/tbody/tr[1]'
      )
      // end "Filter by type"
      .performance.endMark()
      .performance.endMark();

    // End measurement transaction for whole test.
    browser.performance.endMeasurement();

    browser.end();
  }
};
