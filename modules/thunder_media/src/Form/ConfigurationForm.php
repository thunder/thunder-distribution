<?php

namespace Drupal\thunder_media\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\RedundantEditableConfigNamesTrait;

/**
 * Configuration form for Thunder media settings.
 */
class ConfigurationForm extends ConfigFormBase {
  use RedundantEditableConfigNamesTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['enable_filefield_remove_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable file field remove button'),
      '#description' => $this->t('Enable this checkbox to enable remove buttons for file fields on inline entity forms.'),
      '#config_target' => 'thunder_media.settings:enable_filefield_remove_button',
    ];

    $form['ai_disclosure_upload_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only allow the AI disclosure to be set on upload'),
      '#description' => $this->t('Enable this checkbox to disable the "AI disclosure" field on image media once it has been uploaded, so it can only be set while uploading the file.'),
      '#config_target' => 'thunder_media.settings:ai_disclosure_upload_only',
    ];

    return parent::buildForm($form, $form_state);
  }

}
