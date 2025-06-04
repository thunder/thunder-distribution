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
  protected int $knownWarnings = 0;

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
    $this->assertSession()->pageTextNotContains((string) $this->translations['Errors found']);
    $this->assertWarningSummaries($expected_warnings);
    $this->clickLink($this->translations['continue anyway']);
    $this->checkForMetaRefresh();
  }

}
