<?php

namespace Drupal\Tests\thunder\FunctionalJavascript;

/**
 * Trait for manipulation of form fields.
 *
 * @package Drupal\Tests\thunder\FunctionalJavascript
 */
trait ThunderFormFieldTestTrait {

  use ThunderJavascriptTrait;

  /**
   * Set value for group of checkboxes.
   *
   * Existing selection will be cleared before new values are applied.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $value
   *   Comma separated values for checkboxes.
   */
  protected function setCheckbox(string $fieldName, string $value): void {
    // UnCheck all checkboxes and check defined.
    $this->getSession()
      ->executeScript("document.querySelectorAll('input[name*=\"{$fieldName}\"]').forEach((elem) => { elem.checked = false; });");

    $checkNames = explode(',', $value);
    foreach ($checkNames as $checkName) {
      $checkBoxName = $fieldName . '[' . trim($checkName) . ']';

      $this->scrollElementInView('[name="' . $checkBoxName . '"]');
      $this->getSession()->getPage()->checkField($checkBoxName);
    }
  }

  /**
   * Set value for defined field name on current page.
   *
   * @param string $fieldName
   *   Field name.
   * @param string|array $value
   *   Value for field.
   */
  public function setFieldValue(string $fieldName, $value): void {
    $page = $this->getSession()->getPage();
    // If field is checkbox list, then use custom functionality to set values.
    // @todo needs documentation.
    $checkboxes = $page->findAll('xpath', "//input[@type=\"checkbox\" and starts-with(@name, \"{$fieldName}[\")]");
    if (!empty($checkboxes)) {
      $this->setCheckbox($fieldName, $value);

      return;
    }

    // If field is date/time field, then set value directly to field.
    $dateTimeFields = $page->findAll('xpath', "//input[(@type=\"date\" or @type=\"time\") and @name=\"{$fieldName}\"]");
    if (!empty($dateTimeFields)) {
      $this->setRawFieldValue($fieldName, $value);

      return;
    }
    $tagifyFields = $page->findAll('xpath', "//input[contains(@class, \"tagify-widget\") and @name=\"{$fieldName}\"]");
    if (!empty($tagifyFields)) {
      $this->setRawFieldValue($fieldName, $value);
      return;
    }

    // Handle specific types of form fields.
    $field = $page->findField($fieldName);
    $this->assertNotEmpty($field, "Field '{$fieldName}' not found on page.");
    $fieldTag = $field->getTagName();
    if ($fieldTag === 'textarea') {
      // Clear text first, before setting value for "textarea" field.
      $this->getSession()
        ->evaluateScript("jQuery('[name=\"{$fieldName}\"]').val('');");
    }
    elseif ($fieldTag === 'select') {
      // Handling of dropdown list.
      $page->selectFieldOption($fieldName, $value, TRUE);
      return;
    }

    $this->scrollElementInView('[name="' . $fieldName . '"]');
    $page->fillField($fieldName, $value);

    $this->assertWaitOnAjaxRequest();
  }

  /**
   * Set fields on current page.
   *
   * @param array $fieldValues
   *   Field values as associative array with field names as keys.
   */
  public function setFieldValues(array $fieldValues): void {
    foreach ($fieldValues as $fieldName => $value) {
      $this->setFieldValue($fieldName, $value);
    }
  }

  /**
   * Set value directly to field value, without formatting applied.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $rawValue
   *   Raw value for field.
   */
  public function setRawFieldValue(string $fieldName, string $rawValue): void {
    // Set date over jQuery, because browser drivers handle input value
    // differently. fe. (Firefox will set it as "value" for field, but Chrome
    // will use it as text for that input field, and in that case final value
    // depends on format used for input field. That's why it's better to set it
    // directly to value, independently from format used.
    $this->getSession()
      ->executeScript("document.querySelector('[name=\"{$fieldName}\"]').value = '{$rawValue}'");
  }

}
