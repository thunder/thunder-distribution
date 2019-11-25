/**
 * @file
 * Start performance measurement for test.
 *
 * This provides a custom command, .performance.startMeasurement()
 *
 * @param {string} serverUrl
 *   The Elastic APM server URL.
 * @param {string} serviceName
 *   The service name used to display time spans inside Kibana APM.
 * @param {string} transactionName
 *   The transaction name used for tagging logged data.
 * @param {string} domain
 *   The testing host domain name.
 *
 * @return {object}
 *   The 'browser' object.
 */

exports.command = function startMeasurement(
  serverUrl,
  transactionName,
  domain
) {
  const browser = this;

  browser.perform(() => {
    if (!browser.apm.isStarted()) {
      browser.apm.start({ serverUrl, serviceName: "NightwatchJS - Test" });
    }

    browser.globals.apmDomain = domain;
    browser.globals.apmTrans = browser.apm.startTransaction(
      transactionName,
      "test"
    );
    browser.globals.apmSpans = [];

    browser
      // We need to open some URL before set cookie.
      .drupalRelativeURL("/")
      .setCookie({
        domain,
        httpOnly: false,
        path: "/",
        name: "traceId",
        value: browser.globals.apmTrans.traceId
      })
      .setCookie({
        domain,
        httpOnly: false,
        path: "/",
        name: "serverUrl",
        value: serverUrl
      });

    // Label set on Node.JS APM agent should be also set for Browser APM agent.
    browser.performance
      .setLabel("branch", process.env.THUNDER_BRANCH)
      .setCookie({
        domain,
        httpOnly: false,
        path: "/",
        name: "branchTag",
        value: process.env.THUNDER_BRANCH
      })
      .performance.setLabel("test", browser.currentTest.name)
      .setCookie({
        domain,
        httpOnly: false,
        path: "/",
        name: "testTag",
        value: browser.currentTest.name
      });
  });

  return browser;
};
