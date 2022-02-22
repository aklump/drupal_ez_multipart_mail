<?php

namespace Drupal\ez_multipart_mail\Plugin\Mail;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Render\Markup;
use Drupal\htmlmail\Helper\HtmlMailHelper;
use Drupal\htmlmail\Plugin\Mail\HtmlMailSystem;

/**
 * Add multipart mail formatting without dependencies to HtmlMailSystem.
 *
 * Note: This is not registered as a plugin because it extends the parent, if it
 * were registered it would appear as it's own plugin, which is not the
 * intention.
 *
 * @link https://www.w3.org/Protocols/rfc1341/7_2_Multipart.html
 */
class EasyMultipartMailFormatter extends HtmlMailSystem {

  /**
   * Format emails according to module settings.
   *
   * Parses the message headers and body into a MailMIME object.  If another
   * module subsequently modifies the body, then format() should be called again
   * before sending.  This is safe because the $message['body'] is not modified.
   *
   * @param array $message
   *   An associative array with at least the following parts:
   *   - headers: An array of (name => value) email headers.
   *   - body: The text/plain or text/html message part.
   *
   * @return array
   *   The formatted $message, ready for sending.
   */
  public function format(array $message) {
    $is_normalized = isset($message['body']['#type']) && 'ez_multipart_mail' === $message['body']['#type'];
    if (!$is_normalized) {
      $unformatted = $message;
      $formatted = parent::format($message);

      // We will only create a multipart message if the formatted version is not
      // already multipart, because it's possible that the formatted version has
      // already created a multipart message, depending on the settings and
      // plugins that may or may not be present on this drupal install.
      $formatted_type = $this->getContentType($formatted);

      // "The Content-Type field for multipart entities requires one parameter,
      // "boundary", which is used to specify the encapsulation boundary."
      // @link https://www.w3.org/Protocols/rfc1341/7_2_Multipart.html#z0
      $is_multipart = strstr($formatted_type, 'boundary') !== FALSE;
      if ($is_multipart) {
        return $message;
      }

      // This comes from \Drupal\Core\Mail\Plugin\Mail\PhpMail::format.
      if (is_array($unformatted['body'])) {
        $eol = $this->siteSettings->get('mail_line_endings', PHP_EOL);
        $unformatted['body'] = implode("$eol$eol", $unformatted['body']);
      }

      $unformatted_type = $this->getContentType($unformatted);
      if (strstr($unformatted_type, 'text/plain') !== FALSE) {
        $unformatted['body'] = MailFormatHelper::htmlToText($unformatted['body']);
      }

      $message['body'] = [
        '#type' => 'ez_multipart_mail',
        '#plain' => Markup::create($unformatted['body']),
        '#html' => Markup::create($formatted['body']),
      ];
    }

    \Drupal::service('renderer')->renderPlain($message['body']);

    if (HtmlMailHelper::htmlMailIsAllowed($message['to'])) {
      $message['headers']['Content-Type'] = 'multipart/alternative;boundary="' . $message['body']['#boundary'] . '"';
      $message['body'] = $message['body']['#children'];
    }
    else {
      $message['body'] = $message['body']['#plain'];
      $message['headers']['Content-Type'] = 'text/plain; charset=utf-8';
    }

    return $message;
  }

  /**
   * Get the content type from a message array.
   *
   * @param array $message
   *   A message array per the drupal mail API.  Expecting the "headers" key.
   *
   * @return string
   *   The content type if found cast to lowercase.
   */
  private function getContentType(array $message): string {
    foreach ($message['headers'] ?? [] as $key => $value) {
      if (strtolower($key) === 'content-type') {
        return strtolower($value);
      }
    }

    return '';
  }

}
