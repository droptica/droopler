<?php

namespace Drupal\smtp\Plugin\Mail;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\smtp\PHPMailer\PHPMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Modify the drupal mail system to use smtp when sending emails.
 * Include the option to choose between plain text or HTML
 *
 * @Mail(
 *   id = "SMTPMailSystem",
 *   label = @Translation("SMTP Mailer"),
 *   description = @Translation("Sends the message, using SMTP.")
 * )
 */
class SMTPMailSystem implements MailInterface, ContainerFactoryPluginInterface {
  protected $AllowHtml;
  protected $smtpConfig;

  /**
   * Logger
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a SMPTMailSystem object.
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $logger) {
    $this->smtpConfig = \Drupal::config('smtp.settings');
    $this->logger = $logger;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('logger.factory'));
  }

  /**
   * Concatenate and wrap the e-mail body for either
   * plain-text or HTML emails.
   *
   * @param $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return
   *   The formatted $message.
   */
  public function format(array $message) {
    $this->AllowHtml = $this->smtpConfig->get('smtp_allowhtml');
    // Join the body array into one string.
    $message['body'] = implode("\n\n", $message['body']);
    if ($this->AllowHtml == 0) {
      // Convert any HTML to plain-text.
      $message['body'] = MailFormatHelper::htmlToText($message['body']);
      // Wrap the mail body for sending.
      $message['body'] = MailFormatHelper::wrapMail($message['body']);
    }
    return $message;
  }

  /**
   * Send the e-mail message.
   *
   * @see drupal_mail()
   *
   * @param $message
   *   A message array, as described in hook_mail_alter().
   * @return
   *   TRUE if the mail was successfully accepted, otherwise FALSE.
   */
  public function mail(array $message) {
    $to = $message['to'];
    $from = $message['from'];
    $body = $message['body'];
    $headers = $message['headers'];
    $subject = $message['subject'];

    // Create a new PHPMailer object - autoloaded from registry.
    $mailer = new PHPMailer();

    // Turn on debugging, if requested.
    if ($this->smtpConfig->get('smtp_debugging') && \Drupal::currentUser()->hasPermission('administer smtp module')) {
      $mailer->SMTPDebug = TRUE;
    }

    // Set the from name.
    $from_name = $this->smtpConfig->get('smtp_fromname');
    if (empty($from_name)) {
      // If value is not defined in settings, use site_name.
      $from_name = \Drupal::config('system.site')->get('name');
    }

    // Set SMTP module email from.
    if (\Drupal::service('email.validator')->isValid($this->smtpConfig->get('smtp_from'))) {
        $from = $this->smtpConfig->get('smtp_from');
        $headers['Sender'] = $from;
        $headers['Return-Path'] = $from;
        $headers['Reply-To'] = $from;
    }

    // Defines the From value to what we expect.
    $mailer->From = $from;
    $mailer->FromName = Unicode::mimeHeaderEncode($from_name);
    $mailer->Sender = $from;

    $hostname = $this->smtpConfig->get('smtp_client_hostname');
    if ($hostname != '') {
      $mailer->Hostname = $hostname;
    }

    $helo = $this->smtpConfig->get('smtp_client_helo');
    if ($helo != '') {
      $mailer->Helo = $helo;
    }

    // Create the list of 'To:' recipients.
    $torecipients = explode(',', $to);
    foreach ($torecipients as $torecipient) {
      $to_comp = $this->_get_components($torecipient);
      $mailer->AddAddress($to_comp['email'], $to_comp['name']);
    }

    // Parse the headers of the message and set the PHPMailer object's settings
    // accordingly.
    foreach ($headers as $key => $value) {
      //watchdog('error', 'Key: ' . $key . ' Value: ' . $value);
      switch (strtolower($key)) {
        case 'from':
          if ($from == NULL or $from == '') {
            // If a from value was already given, then set based on header.
            // Should be the most common situation since drupal_mail moves the
            // from to headers.
            $from           = $value;
            $mailer->From     = $value;
            // then from can be out of sync with from_name !
            $mailer->FromName = '';
            $mailer->Sender   = $value;
          }
          break;
        case 'content-type':
          // Parse several values on the Content-type header, storing them in an array like
          // key=value -> $vars['key']='value'
          $vars = explode(';', $value);
          foreach ($vars as $i => $var) {
            if ($cut = strpos($var, '=')) {
              $new_var = trim(strtolower(substr($var, $cut + 1)));
              $new_key = trim(substr($var, 0, $cut));
              unset($vars[$i]);
              $vars[$new_key] = $new_var;
            }
          }
          // Set the charset based on the provided value, otherwise set it to UTF-8 (which is Drupals internal default).
          $mailer->CharSet = isset($vars['charset']) ? $vars['charset'] : 'UTF-8';
          // If $vars is empty then set an empty value at index 0 to avoid a PHP warning in the next statement
          $vars[0] = isset($vars[0])?$vars[0]:'';

          switch ($vars[0]) {
            case 'text/plain':
              // The message includes only a plain text part.
              $mailer->IsHTML(FALSE);
              $content_type = 'text/plain';
              break;
            case 'text/html':
              // The message includes only an HTML part.
              $mailer->IsHTML(TRUE);
              $content_type = 'text/html';
              break;
            case 'multipart/related':
              // Get the boundary ID from the Content-Type header.
              $boundary = $this->_get_substring($value, 'boundary', '"', '"');

              // The message includes an HTML part w/inline attachments.
              $mailer->ContentType = $content_type = 'multipart/related; boundary="' . $boundary . '"';
            break;
            case 'multipart/alternative':
              // The message includes both a plain text and an HTML part.
              $mailer->ContentType = $content_type = 'multipart/alternative';

              // Get the boundary ID from the Content-Type header.
              $boundary = $this->_get_substring($value, 'boundary', '"', '"');
            break;
            case 'multipart/mixed':
              // The message includes one or more attachments.
              $mailer->ContentType = $content_type = 'multipart/mixed';

              // Get the boundary ID from the Content-Type header.
              $boundary = $this->_get_substring($value, 'boundary', '"', '"');
            break;
            default:
              // Everything else is unsuppored by PHPMailer.
              drupal_set_message(t('The %header of your message is not supported by PHPMailer and will be sent as text/plain instead.', ['%header' => "Content-Type: $value"]), 'error');
              $this->logger->error(t('The %header of your message is not supported by PHPMailer and will be sent as text/plain instead.', ['%header' => "Content-Type: $value"]));

              // Force the Content-Type to be text/plain.
              $mailer->IsHTML(FALSE);
              $content_type = 'text/plain';
          }
          break;

        case 'reply-to':
          // Only add a "reply-to" if it's not the same as "return-path".
          if ($value != $headers['Return-Path']) {
            $reply_to_comp = $this->_get_components($value);
            $mailer->AddReplyTo($reply_to_comp['email'], Unicode::mimeHeaderEncode($reply_to_comp['name']));
          }
          break;

        case 'content-transfer-encoding':
          $mailer->Encoding = $value;
          break;

        case 'return-path':
          $mailer->Sender = $value;
          break;

        case 'mime-version':
        case 'x-mailer':
          // Let PHPMailer specify these.
          break;

        case 'errors-to':
          $mailer->AddCustomHeader('Errors-To: ' . $value);
          break;

        case 'cc':
          $ccrecipients = explode(',', $value);
          foreach ($ccrecipients as $ccrecipient) {
            $cc_comp = $this->_get_components($ccrecipient);
            $mailer->AddCC($cc_comp['email'], Unicode::mimeHeaderEncode($cc_comp['name']));
          }
          break;

        case 'bcc':
          $bccrecipients = explode(',', $value);
          foreach ($bccrecipients as $bccrecipient) {
            $bcc_comp = $this->_get_components($bccrecipient);
            $mailer->AddBCC($bcc_comp['email'], Unicode::mimeHeaderEncode($bcc_comp['name']));
          }
          break;

        default:
          // The header key is not special - add it as is.
          $mailer->AddCustomHeader($key . ': ' . $value);
      }
    }

    /**
     * TODO
     * Need to figure out the following.
     *
     * Add one last header item, but not if it has already been added.
     * $errors_to = FALSE;
     * foreach ($mailer->CustomHeader as $custom_header) {
     *   if ($custom_header[0] = '') {
     *     $errors_to = TRUE;
     *   }
     * }
     * if ($errors_to) {
     *   $mailer->AddCustomHeader('Errors-To: '. $from);
     * }
     */
    // Add the message's subject.
    $mailer->Subject = Unicode::mimeHeaderEncode($subject);

    // Processes the message's body.
    switch ($content_type) {
      case 'multipart/related':
        $mailer->Body = $body;
        // TODO: Figure out if there is anything more to handling this type.
        break;

      case 'multipart/alternative':
        // Split the body based on the boundary ID.
        $body_parts = $this->_boundary_split($body, $boundary);
        foreach ($body_parts as $body_part) {
          // If plain/text within the body part, add it to $mailer->AltBody.
          if (strpos($body_part, 'text/plain')) {
            // Clean up the text.
            $body_part = trim($this->_remove_headers(trim($body_part)));
            // Include it as part of the mail object.
            $mailer->AltBody = $body_part;
          }
          // If plain/html within the body part, add it to $mailer->Body.
          elseif (strpos($body_part, 'text/html')) {
            // Clean up the text.
            $body_part = trim($this->_remove_headers(trim($body_part)));
            // Include it as part of the mail object.
            $mailer->Body = $body_part;
          }
        }
        break;

      case 'multipart/mixed':
        // Split the body based on the boundary ID.
        $body_parts = $this->_boundary_split($body, $boundary);

        // Determine if there is an HTML part for when adding the plain text part.
        $text_plain = FALSE;
        $text_html  = FALSE;
        foreach ($body_parts as $body_part) {
          if (strpos($body_part, 'text/plain')) {
            $text_plain = TRUE;
          }
          if (strpos($body_part, 'text/html')) {
            $text_html = TRUE;
          }
        }

        foreach ($body_parts as $body_part) {
          // If test/plain within the body part, add it to either
          // $mailer->AltBody or $mailer->Body, depending on whether there is
          // also a text/html part ot not.
          if (strpos($body_part, 'multipart/alternative')) {
            // Get boundary ID from the Content-Type header.
            $boundary2 = $this->_get_substring($body_part, 'boundary', '"', '"');
            // Clean up the text.
            $body_part = trim($this->_remove_headers(trim($body_part)));
            // Split the body based on the boundary ID.
            $body_parts2 = $this->_boundary_split($body_part, $boundary2);

            foreach ($body_parts2 as $body_part2) {
              // If plain/text within the body part, add it to $mailer->AltBody.
              if (strpos($body_part2, 'text/plain')) {
                // Clean up the text.
                $body_part2 = trim($this->_remove_headers(trim($body_part2)));
                // Include it as part of the mail object.
                $mailer->AltBody = $body_part2;
                $mailer->ContentType = 'multipart/mixed';
              }
              // If plain/html within the body part, add it to $mailer->Body.
              elseif (strpos($body_part2, 'text/html')) {
                // Get the encoding.
                $body_part2_encoding = $this->_get_substring($body_part2, 'Content-Transfer-Encoding', ' ', "\n");
                // Clean up the text.
                $body_part2 = trim($this->_remove_headers(trim($body_part2)));
                // Check whether the encoding is base64, and if so, decode it.
                if (Unicode::strtolower($body_part2_encoding) == 'base64') {
                  // Include it as part of the mail object.
                  $mailer->Body = base64_decode($body_part2);
                  // Ensure the whole message is recoded in the base64 format.
                  $mailer->Encoding = 'base64';
                }
                else {
                  // Include it as part of the mail object.
                  $mailer->Body = $body_part2;
                }
                $mailer->ContentType = 'multipart/mixed';
              }
            }
          }
          // If text/plain within the body part, add it to $mailer->Body.
          elseif (strpos($body_part, 'text/plain')) {
            // Clean up the text.
            $body_part = trim($this->_remove_headers(trim($body_part)));

            if ($text_html) {
              $mailer->AltBody = $body_part;
              $mailer->IsHTML(TRUE);
              $mailer->ContentType = 'multipart/mixed';
            }
            else {
              $mailer->Body = $body_part;
              $mailer->IsHTML(FALSE);
              $mailer->ContentType = 'multipart/mixed';
            }
          }
          // If text/html within the body part, add it to $mailer->Body.
          elseif (strpos($body_part, 'text/html')) {
            // Clean up the text.
            $body_part = trim($this->_remove_headers(trim($body_part)));
            // Include it as part of the mail object.
            $mailer->Body = $body_part;
            $mailer->IsHTML(TRUE);
            $mailer->ContentType = 'multipart/mixed';
          }
          // Add the attachment.
          elseif (strpos($body_part, 'Content-Disposition: attachment;') && !isset($message['params']['attachments'])) {
            $file_path     = $this->_get_substring($body_part, 'filename=', '"', '"');
            $file_name     = $this->_get_substring($body_part, ' name=', '"', '"');
            $file_encoding = $this->_get_substring($body_part, 'Content-Transfer-Encoding', ' ', "\n");
            $file_type     = $this->_get_substring($body_part, 'Content-Type', ' ', ';');

            if (file_exists($file_path)) {
              if (!$mailer->AddAttachment($file_path, $file_name, $file_encoding, $file_type)) {
                drupal_set_message(t('Attahment could not be found or accessed.'));
              }
            }
            else {
              // Clean up the text.
              $body_part = trim($this->_remove_headers(trim($body_part)));

              if (Unicode::strtolower($file_encoding) == 'base64') {
                $attachment = base64_decode($body_part);
              }
              elseif (Unicode::strtolower($file_encoding) == 'quoted-printable') {
                $attachment = quoted_printable_decode($body_part);
              }
              else {
                $attachment = $body_part;
              }

              $attachment_new_filename = \Drupal::service('file_system')->tempnam('temporary://', 'smtp');
              $file_path = file_save_data($attachment, $attachment_new_filename, FILE_EXISTS_REPLACE);
              $real_path = \Drupal::service('file_system')->realpath($file_path->uri);

              if (!$mailer->AddAttachment($real_path, $file_name)) {
                drupal_set_message(t('Attachment could not be found or accessed.'));
              }
            }
          }
        }
        break;

      default:
        $mailer->Body = $body;
        break;
    }

    // Process mimemail attachments, which are prepared in mimemail_mail().
    if (isset($message['params']['attachments'])) {
      foreach ($message['params']['attachments'] as $attachment) {
        if (isset($attachment['filecontent'])) {
          $mailer->AddStringAttachment($attachment['filecontent'], $attachment['filename'], 'base64', $attachment['filemime']);
        }
        if (isset($attachment['filepath'])) {
          $filename = isset($attachment['filename']) ? $attachment['filename'] : basename($attachment['filepath']);
          $filemime = isset($attachment['filemime']) ? $attachment['filemime'] : file_get_mimetype($attachment['filepath']);
          $mailer->AddAttachment($attachment['filepath'], $filename, 'base64', $filemime);
        }
      }
    }

    // Set the authentication settings.
    $username = $this->smtpConfig->get('smtp_username');
    $password = $this->smtpConfig->get('smtp_password');

    // If username and password are given, use SMTP authentication.
    if ($username != '' && $password != '') {
      $mailer->SMTPAuth = TRUE;
      $mailer->Username = $username;
      $mailer->Password = $password;
    }


    // Set the protocol prefix for the smtp host.
    switch ($this->smtpConfig->get('smtp_protocol')) {
      case 'ssl':
        $mailer->SMTPSecure = 'ssl';
        break;

      case 'tls':
        $mailer->SMTPSecure = 'tls';
        break;

      default:
        $mailer->SMTPSecure = '';
    }


    // Set other connection settings.
    $mailer->Host = $this->smtpConfig->get('smtp_host') . ';' . $this->smtpConfig->get('smtp_hostbackup');
    $mailer->Port = $this->smtpConfig->get('smtp_port');
    $mailer->Mailer = 'smtp';

    $mailerArr = [
      'mailer' => $mailer,
      'to' => $to,
      'from' => $from,
    ];
    if ($this->smtpConfig->get('smtp_queue')) {
      $this->logger->info(t('Queue sending mail to: @to', ['@to' => $to]));
      smtp_send_queue($mailerArr);
    }
    else {
      return _smtp_mailer_send($mailerArr);
    }

    return TRUE;
  }

  /**
   * Splits the input into parts based on the given boundary.
   *
   * Swiped from Mail::MimeDecode, with modifications based on Drupal's coding
   * standards and this bug report: http://pear.php.net/bugs/bug.php?id=6495
   *
   * @param input
   *   A string containing the body text to parse.
   * @param boundary
   *   A string with the boundary string to parse on.
   * @return
   *   An array containing the resulting mime parts
   */
  protected function _boundary_split($input, $boundary) {
    $parts       = [];
    $bs_possible = substr($boundary, 2, -2);
    $bs_check    = '\"' . $bs_possible . '\"';

    if ($boundary == $bs_check) {
      $boundary = $bs_possible;
    }

    $tmp = explode('--' . $boundary, $input);

    for ($i = 1; $i < count($tmp); $i++) {
      if (trim($tmp[$i])) {
        $parts[] = $tmp[$i];
      }
    }

    return $parts;
  }  //  End of _smtp_boundary_split().

  /**
   * Strips the headers from the body part.
   *
   * @param input
   *   A string containing the body part to strip.
   * @return
   *   A string with the stripped body part.
   */
  protected function _remove_headers($input) {
    $part_array = explode("\n", $input);

    // Will strip these headers according to RFC2045.
    $headers_to_strip = ['Content-Type', 'Content-Transfer-Encoding', 'Content-ID', 'Content-Disposition'];
    $pattern = '/^(' . implode('|', $headers_to_strip) . '):/';

    while (count($part_array) > 0) {

      // Ignore trailing spaces/newlines.
      $line = rtrim($part_array[0]);

      // If the line starts with a known header string.
      if (preg_match($pattern, $line)) {
        $line = rtrim(array_shift($part_array));
        // Remove line containing matched header.

        // If line ends in a ';' and the next line starts with four spaces, it's a continuation
        // of the header split onto the next line. Continue removing lines while we have this condition.
        while (substr($line, -1) == ';' && count($part_array) > 0 && substr($part_array[0], 0, 4) == '    ') {
          $line = rtrim(array_shift($part_array));
        }
      }
      else {
        // No match header, must be past headers; stop searching.
        break;
      }
    }

    $output = implode("\n", $part_array);
    return $output;
  }

  /**
   * Returns a string that is contained within another string.
   *
   * Returns the string from within $source that is some where after $target
   * and is between $beginning_character and $ending_character.
   *
   * @param $source
   *   A string containing the text to look through.
   * @param $target
   *   A string containing the text in $source to start looking from.
   * @param $beginning_character
   *   A string containing the character just before the sought after text.
   * @param $ending_character
   *   A string containing the character just after the sought after text.
   * @return
   *   A string with the text found between the $beginning_character and the
   *   $ending_character.
   */
  protected function _get_substring($source, $target, $beginning_character, $ending_character) {
    $search_start     = strpos($source, $target) + 1;
    $first_character  = strpos($source, $beginning_character, $search_start) + 1;
    $second_character = strpos($source, $ending_character, $first_character) + 1;
    $substring        = substr($source, $first_character, $second_character - $first_character);
    $string_length    = strlen($substring) - 1;

    if ($substring[$string_length] == $ending_character) {
      $substring = substr($substring, 0, $string_length);
    }

    return $substring;
  }

  /**
   * Returns an array of name and email address from a string.
   *
   * @param $input
   *  A string that contains different possible combinations of names and
   *  email address.
   * @return
   *  An array containing a name and an email address.
   */
  protected function _get_components($input) {
    $components = [
      'input' => $input,
      'name' => '',
      'email' => '',
    ];

    // If the input is a valid email address in its entirety, then there is
    // nothing to do, just return that.
    if (\Drupal::service('email.validator')->isValid(trim($input))) {
      $components['email'] = trim($input);
      return $components;
    }

    // Check if $input has one of the following formats, extract what we can:
    //  some name <address@example.com>
    //  "another name" <address@example.com>
    //  <address@example.com>
    if (preg_match('/^"?([^"\t\n]*)"?\s*<([^>\t\n]*)>$/', trim($input), $matches)) {
      $components['name'] = trim($matches[1]);
      $components['email'] = trim($matches[2]);
    }

    return $components;
  }
}
