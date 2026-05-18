<?php

declare(strict_types=1);

namespace Drupal\d_p_subscribe_file\Plugin\Mail;

use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Sends the d_p_subscribe_file download-link email as multipart-free HTML.
 *
 * Drupal's default `php_mail` plugin calls MailFormatHelper::htmlToText() in
 * format(), which strips tags, leaks `<style>` content as plain text and
 * appends `[N]` footnote markers to anchors. The download-link email is
 * authored as an HTML document, so we override format() to leave the rendered
 * markup intact and rely on the `Content-Type: text/html` header set by the
 * module's `hook_mail` implementation.
 */
#[Mail(
  id: 'subscribe_file_html_mail',
  label: new TranslatableMarkup('Droopler subscribe-file HTML mail'),
  description: new TranslatableMarkup('Sends the download-link email as HTML, without converting the body to plain text.'),
)]
class SubscribeFileHtmlMail extends PhpMail {

  /**
   * {@inheritdoc}
   */
  public function format(array $message): array {
    $message['body'] = implode("\n\n", array_map(
      static fn ($part): string => (string) $part,
      $message['body'],
    ));
    return $message;
  }

}
