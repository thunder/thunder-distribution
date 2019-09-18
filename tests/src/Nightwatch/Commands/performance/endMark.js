/**
 * @file
 * Ends last performance measurement mark.
 *
 * This provides a custom command, .performance.endMark()
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function endMark() {
  const browser = this;

  browser.performance.waitBrowser().perform(() => {
    let span = browser.globals.apmSpans.pop();

    if (!span) {
      return;
    }

    span.end();

    // Set spanId to current active span, if there is any.
    span = browser.globals.apmSpans.pop();

    if (!span) {
      return;
    }

    browser.setCookie({
      domain: browser.globals.apmDomain,
      httpOnly: false,
      name: "spanId",
      path: "/",
      value: span.id
    });
    browser.globals.apmSpans.push(span);
  });

  return browser;
};
