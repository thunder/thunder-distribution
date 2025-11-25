<?php

namespace Drupal\thunder_paragraphs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Thunder Paragraphs click-to-edit settings.
 */
class ThunderParagraphsClickEditSettingsForm extends ConfigFormBase {

  /**
   * Get Form Id.
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
   * Build Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('thunder_paragraphs.settings');
    $default_selector = '.paragraphs-item, .paragraph-form-item--has-preview, [id^="field-paragraphs-"][id*="-item-wrapper"]';

    $form['click_edit'] = [
      '#type' => 'details',
      '#title' => $this->t('Click-to-edit'),
      '#open' => TRUE,
    ];

    $form['click_edit']['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Wrapper CSS selector'),
      '#default_value' => $config->get('click_edit.selector') ?: $default_selector,
      '#description' => $this->t('Selector used to attach click-to-edit. Keep it specific to paragraph wrappers.'),
      '#maxlength' => 500,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $selector = trim($form_state->getValue('selector'));
    if ($selector === '') {
      $form_state->setErrorByName('selector', $this->t('Selector cannot be empty.'));
    }
    if (preg_match('/[<>]/', $selector)) {
      $form_state->setErrorByName('selector', $this->t('Invalid characters in selector.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * Submit Form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('thunder_paragraphs.settings')
      ->set('click_edit.selector', trim($form_state->getValue('selector')))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
