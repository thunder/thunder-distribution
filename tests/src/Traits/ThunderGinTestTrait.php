<?php

namespace Drupal\Tests\thunder\Traits;

/**
 * This trait provides overriden functions for the gin theme.
 *
 * The form_submit function was added to fix
 * https://www.drupal.org/project/gin/issues/3455524
 * remove after
 * https://www.drupal.org/project/drupal/issues/3456202
 * has landed.
 */
trait ThunderGinTestTrait {

  /**
   * Fills and submits a form.
   *
   * @param array $edit
   *   Field data in an associative array. Changes the current input fields
   *   (where possible) to the values indicated.
   *
   *   A checkbox can be set to TRUE to be checked and should be set to FALSE to
   *   be unchecked.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated. For example,
   *   'Save'. The processing of the request depends on this value. For example,
   *   a form may have one button with the value 'Save' and another button with
   *   the value 'Delete', and execute different code depending on which one is
   *   clicked.
   * @param string $form_html_id
   *   (optional) HTML ID of the form to be submitted. On some pages
   *   there are many identical forms, so just using the value of the submit
   *   button is not enough. For example: 'trigger-node-presave-assign-form'.
   *   Note that this is not the Drupal $form_id, but rather the HTML ID of the
   *   form, which is typically the same thing but with hyphens replacing the
   *   underscores.
   */
  public function submitForm(array $edit, $submit, $form_html_id = NULL): void {
    $assert_session = $this->assertSession();
    $submit_button = $assert_session->buttonExists($submit);

    // Check if button has a form attribute set.
    if ($form_id = $submit_button->getAttribute('form')) {
      $form = $assert_session->elementExists('xpath', "//form[@id='$form_id']");
      $action = $form->getAttribute('action');
    }
    // Get the form.
    elseif (isset($form_html_id)) {
      $form = $assert_session->elementExists('xpath', "//form[@id='$form_html_id']");
      $submit_button = $assert_session->buttonExists($submit, $form);
      $action = $form->getAttribute('action');
    }
    else {
      // Gin Form Test: Change check to include //form
      // so we keep the search in scope of a form.
      $submit_button = $assert_session->elementExists('xpath', "//input[@value='$submit']");
      $form = $assert_session->elementExists('xpath', './ancestor::form', $submit_button);
      $action = $form->getAttribute('action');
    }

    // Edit the form values.
    foreach ($edit as $name => $value) {
      // Fields / buttons in static area are not in form context.
      $field = $assert_session->fieldExists($name);

      // Provide support for the values '1' and '0' for checkboxes instead of
      // TRUE and FALSE.
      // @todo Get rid of supporting 1/0 by converting all tests cases using
      // this to boolean values.
      $field_type = $field->getAttribute('type');
      if ($field_type === 'checkbox') {
        $value = (bool) $value;
      }
      $field->setValue($value);
    }

    // Submit form.
    $this->prepareRequest();
    $submit_button->press();

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    // Check if there are any meta refresh redirects (like Batch API pages).
    if ($this->checkForMetaRefresh()) {
      // We are finished with all meta refresh redirects, so reset the counter.
      $this->metaRefreshCount = 0;
    }

    // Log only for WebDriverTestBase tests because for tests using
    // DrupalTestBrowser we log with ::getResponseLogHandler.
    if ($this->htmlOutputEnabled && !$this->isTestUsingGuzzleClient()) {
      $out = $this->getSession()->getPage()->getContent();
      $html_output = 'POST request to: ' . $action .
        '<hr />Ending URL: ' . $this->getSession()->getCurrentUrl();
      $html_output .= '<hr />' . $out;
      $html_output .= $this->getHtmlOutputHeaders();
      $this->htmlOutput($html_output);
    }

  }

}
