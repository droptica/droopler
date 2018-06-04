<?php

namespace Drupal\paragraphs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for Paragraphs settings.
 */
class ParagraphsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paragraphs_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['paragraphs.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('paragraphs.settings');
    $form['show_unpublished'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show unpublished Paragraphs'),
      '#default_value' => $config->get('show_unpublished'),
      '#description' => $this->t('Allow users with "View unpublished paragraphs" permission to see unpublished Paragraphs.')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('paragraphs.settings');
    $config->set('show_unpublished', $form_state->getValue('show_unpublished'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
