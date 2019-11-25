/**
 * @file
 * Sets performance measurement mark.
 *
 * This provides a custom command, .performance.startMark()
 *
 * @param {string} markName
 *   The mark name used for naming of time spans.
 *
 * @return {object}
 *   The 'browser' object.
 */

exports.command = function startMark(markName) {
  const browser = this;

  browser.perform(() => {
    const span = browser.globals.apmTrans.startSpan(markName);

    span.setLabel("branch", process.env.THUNDER_BRANCH);
    span.setLabel("test", browser.currentTest.name);

    browser.globals.apmSpans.push(span);

    browser.setCookie({
      domain: browser.globals.apmDomain,
      httpOnly: false,
      name: "spanId",
      path: "/",
      value: span.id
    });
  });

  return browser;
};
