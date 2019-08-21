/**
 * @file
 * Testing of content creation for most used bundle.
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
// eslint-disable-next-line import/no-dynamic-require
const request = require(`${process.cwd()}/node_modules/request`);

module.exports = {
  "@tags": ["Thunder"],
  before(browser, done) {
    browser.apm = apm;

    const baseUrl = process.env.DRUPAL_TEST_BASE_URL;
    request(
      `${baseUrl}/thunder-performance-measurement/site-info/?rule=count&index=0`,
      (error, response, body) => {
        // Get Site information first!!!
        const { data } = JSON.parse(body);

        browser._site_info = data;

        done();
      }
    );
  },
  createMostUsedContentBundle(browser) {
    const { bundle, required_fields: requiredFields } = browser._site_info;

    browser
      .resizeWindow(1024, 1024)
      .drupalRelativeURL("/user/login")
      .performance.startMeasurement(
        process.env.THUNDER_APM_URL,
        "NightwatchJS - Test",
        "Create new most used content bundle (min)",
        `.${process.env.THUNDER_SITE_HOSTNAME}`
      )
      .performance.startMark("full task")
      .performance.startMark("login")
      .drupalLogin({ name: "test-admin", password: "test-admin" })
      .performance.endMark()

      .performance.startMark("create new most used content bundle")
      .drupalRelativeURL(`/node/add/${bundle}`)
      // Start using XPATH!!!
      .useXpath()
      .waitForElementVisible('//*[@id="edit-submit"]', 1000);

    // Fill required fields for content bundle.
    browser.performance.startMark("fill required fields");
    const requiredFieldNames = Object.keys(requiredFields);
    requiredFieldNames.forEach(fieldName => {
      browser.fieldAutoFill(fieldName, requiredFields[fieldName]);
    });
    browser.performance.endMark();

    // Close mark and save newly created content bundle entity.
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
