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
      // End "login".
      .performance.endMark()
      .performance.startMark("Open content overview page")
      .drupalRelativeURL("/admin/t0_node_bundle_0")
      .useXpath()
      .waitForElementPresent(
        '//*[@id="block-thunder-admin-content"]/div/div/nav/ul/li[1]/a'
      )
      // End "Open content overview page".
      .performance.endMark()
      .performance.startMark("Filter by type")
      .setValue('//*[@id="edit-type"]', "bundle_6")
      .click('//*[@id="edit-submit-t0-node-bundle-0"]')
      .waitForElementPresent(
        '//*[@id="block-thunder-admin-content"]/div/div/nav[@class="pager"]/ul/li[1]/a[contains(@href, "bundle_6")]'
      )
      // End "Filter by type".
      .performance.endMark()
      // End full task.
      .performance.endMark();

    // End measurement transaction for whole test.
    browser.performance.endMeasurement();

    browser.end();
  },
};
