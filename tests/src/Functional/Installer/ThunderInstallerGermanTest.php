<?php

namespace Drupal\Tests\thunder\Functional\Installer;

/**
 * Tests the interactive installer installing the standard profile.
 *
 * @group ThunderInstaller
 */
class ThunderInstallerGermanTest extends ThunderInstallerTest {

  /**
   * {@inheritdoc}
   */
  protected $knownWarnings = 0;

  /**
   * {@inheritdoc}
   */
  protected $langcode = 'de';

  /**
   * {@inheritdoc}
   */
  protected $translations = [
    'Save and continue' => 'Speichern und fortfahren',
    'Errors found' => 'Fehler gefunden',
    'continue anyway' => 'fortfahren',
  ];

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller(): void {
    include_once DRUPAL_ROOT . '/core/includes/install.core.inc';
    $version = _install_get_version_info(\Drupal::VERSION)['major'] . '.0.0';

    // Place custom local translations in the translations directory to avoid
    // using the Internet and relying on https://localize.drupal.org/.
    mkdir(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . "/files/translations/drupal-{$version}.{$this->langcode}.po", $this->getPo($this->langcode));

    parent::visitInstaller();
  }

  /**
   * Returns the string for the test .po file.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   Contents for the test .po file.
   */
  protected function getPo(string $langcode): string {
    return <<<ENDPO
msgid ""
msgstr ""

msgid "Congratulations, you installed @drupal!"
msgstr "Glückwunsch, @drupal wurde erfolgreich installiert."

msgid "Save and continue"
msgstr "Speichern und fortfahren"

msgid "continue anyway"
msgstr "fortfahren"

msgid "Errors found"
msgstr "Fehler gefunden"

ENDPO;
  }

  /**
   * {@inheritdoc}
   */
  protected function continueOnExpectedWarnings($expected_warnings = []): void {
    // Don't try to continue if there are errors.
    if (strpos($this->getTextContent(), (string) $this->translations['Errors found']) !== FALSE) {
      return;
    }
    // Allow only details elements that are directly after the warning header
    // or each other. There is no guaranteed wrapper we can rely on across
    // distributions. When there are multiple warnings, the selectors will be:
    // - h3#warning+details summary
    // - h3#warning+details+details summary
    // - etc.
    // We add one more selector than expected warnings to confirm that there
    // isn't any other warning before clicking the link.
    // @todo Make this more reliable in
    //   https://www.drupal.org/project/drupal/issues/2927345.
    $selectors = [];
    for ($i = 0; $i <= count($expected_warnings); $i++) {
      $selectors[] = 'h3#warning' . implode('', array_fill(0, $i + 1, '+details')) . ' summary';
    }
    $warning_elements = $this->cssSelect(implode(', ', $selectors));

    // Confirm that there are only the expected warnings.
    $warnings = [];
    foreach ($warning_elements as $warning) {
      $warnings[] = trim($warning->getText());
    }
    $this->assertEquals($expected_warnings, $warnings);
    $this->clickLink($this->translations['continue anyway']);
    $this->checkForMetaRefresh();
  }

}
