<?php

namespace Drupal\d_mautic\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides settings form for Mautic service integration.
 *
 * @package Drupal\d_mautic\Form
 */
class MauticSettingsForm extends ConfigFormBase {

  /**
   * Mautic token.
   *
   * @var array
   */
  protected $accessToken;

  /**
   * The predefined field value for the form.
   *
   * @var array
   */
  protected $formData;

  /**
   * MauticSettingsForm constructor.
   */
  public function __construct() {
    $this->formData = $this->config('d_mautic.settings');
    $this->accessToken = $this->formData->get('d_mautic_access_token');
    $this->authorize();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mautic_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'd_mautic.settings',
    ];
  }

  /**
   * Initiates Mautic API authorization.
   *
   * @param bool $flag
   */
  private function authorize($flag = FALSE) {
    if (isset($_GET['code']) && $_GET['code'] != '' || $flag) {
      \Drupal::service('d_mautic.contact')->authorize();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => isset($this->accessToken) ? $this->t('Reauthorizate') : $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    $form['#theme'] = 'system_config_form';

    $form['d_mautic_description'] = [
      '#markup' => $this->t("Set URL, Client ID and Client Secret defined in your Mautic service. The authorization method is OAuth 2."),
    ];

    $form['d_mautic_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Mautic Base URL'),
      '#default_value' => $this->formData->get('d_mautic_url'),
      '#required' => TRUE,
    ];

    $form['d_mautic_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $this->formData->get('d_mautic_client_id'),
      '#required' => TRUE,
    ];

    $form['d_mautic_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#default_value' => $this->formData->get('d_mautic_client_secret'),
      '#required' => TRUE,
    ];

    $form['d_mautic_redirect_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URI. It should be the same as you entered, when you were creating your Mautic API credentials.'),
      '#placeholder' => 'http(s)://your-website.com/admin/application/mautic-settings/',
      '#default_value' => $this->formData->get('d_mautic_redirect_uri'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('d_mautic.settings')
      ->set('d_mautic_url', trim($form_state->getValue('d_mautic_url'), '/ '))
      ->set('d_mautic_client_id', trim($form_state->getValue('d_mautic_client_id')))
      ->set('d_mautic_client_secret', trim($form_state->getValue('d_mautic_client_secret')))
      ->set('d_mautic_redirect_uri', trim($form_state->getValue('d_mautic_redirect_uri')))
      ->set('d_mautic_access_token', '')
      ->set('d_mautic_expires', '')
      ->set('d_mautic_token_type', '')
      ->set('d_mautic_refresh_token', '')
      ->save();

    $this->authorize(TRUE);

    parent::submitForm($form, $form_state);
  }

}
