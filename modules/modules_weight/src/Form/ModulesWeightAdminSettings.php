<?php

namespace Drupal\modules_weight\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ModulesWeightAdminSettings.
 */
class ModulesWeightAdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'modules_weight.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'modules_weight_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('modules_weight.settings');

    $form['show_system_modules'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show system modules'),
      '#description' => $this->t("Cautions: This module just display non-core modules by default, if you check this option you will be able to change the core modules weight and as you might notice, all core modules has 0 as weight value by default."),
      '#default_value' => $config->get('show_system_modules'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Saving the module configuration.
    $this->config('modules_weight.settings')
      ->set('show_system_modules', $form_state->getValue('show_system_modules'))
      ->save();
  }

}
