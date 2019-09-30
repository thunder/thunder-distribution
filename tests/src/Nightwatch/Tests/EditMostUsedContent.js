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
        index: 0
      },
      browser,
      done
    );
  },
  editMostUsedContent(browser) {
    const { bundle, required_fields: requiredFields } = browser._site_info;

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
      .performance.startMark("load edit form")
      .click(
        "/html/body/div[2]/div/main/div[4]/div[2]/table/tbody/tr[1]/td[6]/div/div/ul/li[1]/a"
      )
      .waitForElementVisible('//*[@id="edit-submit"]', 1000)
      .performance.endMark()
      .performance.startMark("edit and save")
      .click('//*[@id="edit-submit"]')
      .waitForElementVisible('//*[@id="block-thunder-admin-content"]', 10000)
      .performance.endMark()
      .performance.endMark();

    // TODO - measure time for loading of edit form
    // 1. Start performance mark - for whole process
    // 2. Start performance mark - for loading of edit form
    // 3. Open first bundle instance for editing -> Click: '(//td[contains(@class,"views-field-operations")])[1]//a[contains(@href, "edit")]'
    // 4. Wait for specific element to be visible, so that we know, loading has finished
    // 5. End performance mark - for loading of edit form
    // 6. Start performance mark - for edit time and save
    // 7. (we can skip this part) do editing -> fe. change title or all "requiredFields"
    // 8. Save instance
    // 9. End performance mark - for edit time and save
    // 10. End performance mark - for whole process

    // End measurement transaction for whole test.
    browser.performance.endMeasurement();

    browser.end();
  }
};
