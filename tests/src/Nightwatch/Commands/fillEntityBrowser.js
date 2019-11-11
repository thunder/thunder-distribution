/**
 * @file
 * Automatically fill entity browser field widget in modal dialog.
 *
 * This provides a custom command, .fillEntityBrowser()
 *
 * TODO: Extend to accept list of entities to select.
 *
 * @param {string} fieldName
 *   The filed name.
 * @param {string} entityBrowserName
 *   The entity browser name.
 * @param {string} selectionMode
 *   The entity browser selection mode.
 *
 * @return {object}
 *   The 'browser' object.
 */
exports.command = function fillEntityBrowser(
  fieldName,
  entityBrowserName,
  selectionMode
) {
  const browser = this;

  const fieldIdPart = fieldName.replace(/[_[]/g, "-").replace(/]/g, "");
  const entityBrowserNameIdPart = entityBrowserName
    .replace(/[_[]/g, "-")
    .replace(/]/g, "");

  browser
    .scrollInViewAndClick(
      `//*[starts-with(@id, "edit-${fieldIdPart}-entity-browser-entity-browser-open-modal")]`
    )
    .waitForElementVisible(
      `//*[@id="entity_browser_iframe_${entityBrowserName}"]`,
      10000
    )
    .frame(`entity_browser_iframe_${entityBrowserName}`)
    .waitForElementVisible(
      `//*[@id="entity-browser-${entityBrowserNameIdPart}-form"]/div[1]/div[2]`,
      10000
    );

  // Select first entity in view.
  browser.executeAsync(
    // eslint-disable-next-line prefer-arrow-callback
    function inBrowser(xpathSelectorInBrowser, done) {
      jQuery(
        document.evaluate(xpathSelectorInBrowser, document).iterateNext()
      ).click();

      done();
    },
    [
      `//*[@id="entity-browser-${entityBrowserNameIdPart}-form"]/div[1]/div[2]/div[1]`
    ],
    () => {}
  );

  // TODO: Fix after we have properly displayed images in entity browser.
  // .waitForElementVisible(
  //   `//*[@id="entity-browser-${entityBrowserNameIdPart}-form"]/div[1]/div[2]/div[1]`,
  //   10000
  // )
  // .click(
  //   `//*[@id="entity-browser-${entityBrowserNameIdPart}-form"]/div[1]/div[2]/div[1]`
  // )

  // Handle multi and single select entity browser.
  const submitSelector =
    selectionMode === "selection_edit"
      ? '//*[starts-with(@id, "edit-use-selected")]'
      : '//*[@id = "edit-submit"]';

  browser
    .click(submitSelector)
    .frame()
    .waitForElementVisible(
      `//*[starts-with(@id, "edit-${fieldIdPart}-current-items-0")]`,
      10000
    );

  return browser;
};
