<?php

namespace Drupal\thunder_paragraphs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Thunder Paragraphs click-to-edit settings.
 */
class ThunderParagraphsClickEditSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'thunder_paragraphs_click_edit_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['thunder_paragraphs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('thunder_paragraphs.settings');

    $form['click_edit'] = [
      '#type' => 'details',
      '#title' => $this->t('Click-to-edit'),
      '#open' => TRUE,
    ];

    $form['click_edit']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable click-to-edit'),
      '#default_value' => (bool) ($config->get('click_edit.enabled') ?? TRUE),
      '#description' => $this->t('Toggle to enable or disable click-to-edit behavior.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('thunder_paragraphs.settings')
      ->set('click_edit.enabled', (bool) $form_state->getValue('enabled'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
