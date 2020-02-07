<?php

namespace Drupal\d_mail\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class Data {

  use StringTranslationTrait;

  /**
   * @return array|mixed|null
   */
  public function getReplyTitle() {
    $title = \Drupal::config('d_mail.settings_page')
      ->get('d_mail_reply_subject');
    $title = mb_encode_mimeheader($title, "UTF-8");

    return $title;
  }

  /**
   * @return array|mixed|null
   */
  public function getReplyMessage() {
    $message = \Drupal::config('d_mail.settings_page')
      ->get('d_mail_reply_message');

    return $message;
  }

  /**
   * @param $topic
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  public function getReturnTitleContact($topic) {
    $title = $this->t('Contact form: @from', ['@from' => mb_encode_mimeheader($topic, "UTF-8")]);

    return $title;
  }

  /**
   * @param $topic
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  public function getReturnTitleContactTestUs() {
    $title = $this->t('Test Us contact form');

    return $title;
  }

  /**
   * Shows input values in mail message.
   *
   * @param array $values
   *
   * @return string
   */
  public function showValues($values = []) {

    $content = '';
    foreach ($values as $key => $value) {
      $value_html = nl2br($value);
      $content .= "<div><strong>" . $key . "</strong>: <br /><br />" . $value_html . "</div><br />";
    }
    $content .= "<div>========================================</div><br />";

    return $content;
  }
}
