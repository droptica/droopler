<?php

namespace Drupal\d_mail\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class MailSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'MailSettings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'd_mail.settings_page',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('d_mail.settings_page');
    $site_mail = \Drupal::config('system.site')->get('mail');

    $form['d_mail_reply_description'] = [
      '#markup' => $this->t('Set title and message text for reply mail to client after contact forms submissions'),
    ];

    $form['d_mail_reply_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Set subject'),
      '#default_value' => $config->get('d_mail_reply_subject') != '' ? $config->get('d_mail_reply_subject') : 'Request received',
    ];

    $form['d_mail_reply_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Set message'),
      '#default_value' => $config->get('d_mail_reply_message') != '' ? $config->get('d_mail_reply_message') : "Type message here <br/><br/>",
    ];

    $form['d_mail_notification_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Set notification email address'),
      '#default_value' => $config->get('d_mail_notification_email') ?: $site_mail,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('d_mail.settings_page')
      ->set('d_mail_reply_subject', $form_state->getValue('d_mail_reply_subject'))
      ->set('d_mail_reply_message', $form_state->getValue('d_mail_reply_message'))
      ->set('d_mail_notification_email', $form_state->getValue('d_mail_notification_email'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
