<?php

namespace Drupal\thunder_paragraphs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Thunder Paragraphs click-to-edit settings to enable.
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
      '#default_value' => (bool) ($config->get('click_edit.enabled')),
      '#description' => $this->t('Toggle to enable or disable click-to-edit behavior.'),
      '#config_target' => 'thunder_paragraphs.settings:click_edit.enabled',
    ];

    return parent::buildForm($form, $form_state);
  }

}
