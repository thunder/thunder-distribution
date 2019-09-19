/**
 * @file
 * Ends performance measurement for a test.
 *
 * This provides a custom command, .performance.endMeasurement()
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function endMeasurement() {
  const browser = this;

  browser.performance.waitBrowser();

  browser
    .perform(() => {
      let span = browser.globals.apmSpans.pop();

      while (span) {
        span.end();

        span = browser.globals.apmSpans.pop();
      }
    })
    .perform(() => {
      browser.globals.apmTrans.end();
    });

  return browser;
};
