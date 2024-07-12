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

    return parent::buildForm($form, $form_state);
  }

}
