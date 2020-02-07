<?php

namespace Drupal\d_mail\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Sending emails.
 *
 * @package Drupal\d_mail\Controller
 */
class Mailer {

  use StringTranslationTrait;

  /**
   * @return string
   */
  private function mailModule() {
    return 'd_mail';
  }

  /**
   * Send email by Drupal mail manager.
   *
   * @param $to
   * @param $replyTo
   * @param array $params
   * @param $key
   *
   * @return mixed
   */
  public function sendMail($to, $replyTo, $params = [], $key) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $lang = \Drupal::service('d_lang.current')->getId();
    $module = $this->mailModule();

    $result = $mailManager->mail($module, $key, $to, $lang, $params, $replyTo, TRUE);

    return $result;
  }

  /**
   * Messages after send mail.
   *
   * @param $resultStatus
   */
  public function setMessages($resultStatus) {
    $lang = \Drupal::service('d_lang.current')->getId();

    switch ($lang) {
      case 'pl':
        $messageSend = 'Wiadomość została wysłana.';
        $messageError = 'Wystąpił problem z wysłaniem wiadomości i nie została ona wysłana.';
        break;

      default:
        $messageSend = $this->t('Your message has been sent.');
        $messageError = $this->t('There was a problem sending your message and it was not sent.');
        break;
    }

    if ($resultStatus !== TRUE) {
      drupal_set_message($messageError, 'error');
    }
    else {
      drupal_set_message($messageSend);
    }

  }

  /**
   * Sending mails on contact forms submit.
   *
   * @param array $values
   * @param string $formId
   *
   * @return bool
   *   True on success.
   */
  public function sendOnFormSubmit($values = [], $formId = '') {
    $email = \Drupal::config('d_mail.settings_page')->get('d_mail_notification_email');
    $data = \Drupal::service('d_mail.data');

    $sendReplyTo = $values['email'];
    $paramsReturn['values'] = $values;
    $paramsReturn['body'] = $data->showValues($values);
    $paramsReply['body'] = $data->getReplyMessage();

    switch ($formId) {
      case 'testus_form':
        $key = 'contact_testus_form_return';
        break;

      default:
        $key = 'contact_form_return';

    }

    // Reply to client.
    $this->sendMail($sendReplyTo, $email, $paramsReply, 'contact_form_reply');

    // Return message
    $sendReturn = $this->sendMail($email, $sendReplyTo, $paramsReturn, $key);

    $this->setMessages($sendReturn['result']);

    return $sendReturn['result'];
  }

  /**
   * Sending mails when errors occur.
   *
   * @param array $data
   * @param string $title
   */
  public function sendErrorReport($data, $title = '') {
    $email_to = \Drupal::config('system.site')->get('mail');
    $from = $email_to;
    $template = 'errors_form';

    // Prepare message content.
    $message_rendered = [
      '#theme' => $template,
      '#data' => $data,
    ];

    $params = [];
    $params['body'] = \Drupal::service('renderer')->render($message_rendered);
    $params['title'] = $this->t($title);

    // Send e-mail.
    $this->sendMail($email_to, $from, $params, $template);

    $logger = [
      'to' => $email_to,
      'params' => $params['body'],
      'from' => $from,
    ];

    \Drupal::logger('d_mail')
      ->notice('[' . $template . ']: <pre>' . print_r($logger, TRUE) . '</pre>');
  }

}
