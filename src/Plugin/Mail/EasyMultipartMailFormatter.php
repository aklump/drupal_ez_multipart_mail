<?php

namespace Drupal\ez_multipart_mail\Plugin\Mail;

use Drupal\Core\Mail\MailFormatHelper;
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

    $eol = $this->siteSettings->get('mail_line_endings', PHP_EOL);
    $boundary = uniqid('np');

    $message['headers']['Content-Type'] = 'multipart/alternative;boundary="' . $boundary . '"';
    $multipart_raw = '';
    $multipart_raw .= 'This is a MIME encoded message.';
    $multipart_raw .= $eol . $eol . "--" . $boundary . $eol;

    // The un-formatted version.
    $unformatted_type = $this->getContentType($unformatted);
    $multipart_raw .= "Content-Type: " . $unformatted_type . $eol . $eol;

    // This comes from \Drupal\Core\Mail\Plugin\Mail\PhpMail::format.
    if (is_array($unformatted['body'])) {
      $unformatted['body'] = implode("$eol$eol", $unformatted['body']);
    }

    if (strstr($unformatted_type, 'text/plain') !== FALSE) {
      $unformatted['body'] = MailFormatHelper::htmlToText($unformatted['body']);
      $unformatted['body'] = MailFormatHelper::wrapMail($unformatted['body']);
    }

    $multipart_raw .= $unformatted['body'];
    $multipart_raw .= $eol . $eol . "--" . $boundary . $eol;

    // The formatted text/html version of the message.
    $multipart_raw .= "Content-Type: " . $this->getContentType($formatted) . $eol . $eol;
    $multipart_raw .= $formatted['body'];
    $multipart_raw .= $eol . $eol . "--" . $boundary . "--";

    $message['body'] = $multipart_raw;

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
