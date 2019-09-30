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
      .drupalLogin({ name: "test-admin", password: "test-admin" })
      .useXpath()
      .performance.startMark("Open content overview page")
      .drupalRelativeURL("/admin/content")
      .waitForElementVisible(
        '//*[@id="block-thunder-admin-content"]/table/tbody/tr[1]',
        1000
      )
      .performance.endMark();

    // TODO - measure time for content overview loading
    // 1. Start performance mark
    // 2. Open content overview page
    // 3. Wait for specific element to be visible, so that we know, loading has finished
    // 4. End performance mark

    // End measurement transaction for whole test.
    browser.performance.endMeasurement();

    browser.end();
  }
};
