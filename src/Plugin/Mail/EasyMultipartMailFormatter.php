<?php

namespace Drupal\ez_multipart_mail\Plugin\Mail;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Render\Markup;
use Drupal\htmlmail\Helper\HtmlMailHelper;
use Drupal\htmlmail\Plugin\Mail\HtmlMailSystem;
use Symfony\Component\Mime\Part\Multipart\AlternativePart;
use Symfony\Component\Mime\Part\TextPart;

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

  const PLAIN = 0;

  const HTML = 1;

  const MIXED = 2;

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
    $message = $this->ensureMessageIsMultipart($message);

    // This step applies all the pre/post processing to our render element.
    // Rather than use #children, we are going to cherry-pick #plain and #html
    // instead.
    \Drupal::service('renderer')->renderPlain($message['body']);

    if (HtmlMailHelper::htmlMailIsAllowed($message['to'])) {
      $html_part = strval($message['body']['#html']);
      $combined = new AlternativePart(
        new TextPart(strval($message['body']['#plain'])),
        new TextPart($html_part, NULL, 'html')
      );
      foreach ($combined->getPreparedHeaders()->all() as $header) {
        $message['headers'][$header->getName()] = $header->getBodyAsString();
      }
      $message['body'] = $combined->bodyToString();
    }
    else {
      $message['body'] = $message['body']['#plain'];
      $message['headers']['Content-Type'] = 'text/plain; charset=utf-8';
    }

    if ($this->configVariables->get('debug')) {
      \Drupal::service('ez_multipart_mail.debugger')->format($message);
    }

    return $message;
  }

  /**
   * Determine the message type.
   *
   * @param array $message
   *   The message array.
   *
   * @return integer
   *
   * @see \Drupal\ez_multipart_mail\Plugin\Mail\EasyMultipartMailFormatter::PLAIN
   * @see \Drupal\ez_multipart_mail\Plugin\Mail\EasyMultipartMailFormatter::HTML
   * @see \Drupal\ez_multipart_mail\Plugin\Mail\EasyMultipartMailFormatter::MIXED
   */
  private function getMessageType(array $message): int {
    $value = '';
    foreach (($message['headers'] ?? []) as $key => $value) {
      if (strtolower($key) === 'content-type') {
        $value = strtolower($value);
        break;
      }
    }
    if (!$value) {
      throw new \InvalidArgumentException('Missing content type header');
    }

    // "The Content-Type field for multipart entities requires one parameter,
    // "boundary", which is used to specify the encapsulation boundary."
    // @link https://www.w3.org/Protocols/rfc1341/7_2_Multipart.html#z0
    if (strstr($value, 'boundary') !== FALSE) {
      return self::MIXED;
    }
    if (strstr($value, '/plain') !== FALSE) {
      return self::PLAIN;
    }
    if (strstr($value, '/html') !== FALSE) {
      return self::HTML;
    }
    throw new \InvalidArgumentException(sprintf('Unknown content type header: %s', $value));
  }

  /**
   * Convert any message to multipart text + html.
   *
   * @param array $message
   *   The message render array per Drupal Mail API.
   *
   * @return array
   *   The message in multipart mime.
   */
  private function ensureMessageIsMultipart(array $message): array {
    if ('ez_multipart_mail' === ($message['body']['#type'] ?? NULL)) {
      return $message;
    }


    // We could force preprocessing in theme_html but this module wants to make
    // life easier for the developer, so we will convert text/plains to
    // text/html automatically.  Plaintext messages come from the system, such
    // as password reset emails.
    if ($this->getMessageType($message) === self::PLAIN) {
      $message = $this->messageToHtml($message);
    }

    // We will send to the parent formatting method for several reasons:
    //  - Ensure the "from" header.
    //  - Leverage the Mail Mime module if installed and configured OR...
    //  - ... run the message through "theme_html_mail*".
    //  - Force to plaintext if user account has disabled HTML mails.
    //  - Apply configured post filters per HTML mail module.
    $formatted_message = parent::format($message);

    // We will only create a multipart message if the formatted version is not
    // already multipart, because it's possible that the formatted version has
    // already created a multipart message, depending on the settings and
    // plugins that may or may not be present on this Drupal install.
    if ($this->getMessageType($formatted_message) === self::MIXED) {
      return $formatted_message;
    }

    // Pull the HTML version from the parent process.
    $html_body = $formatted_message['body'];
    unset($formatted_message);

    // At this point we have still need to create a multipart message, therefor
    // we need to ensure we have the plaintext and the HTML parts and then
    // assemble in an ez_multipart_mail #type render array.

    // This comes from \Drupal\Core\Mail\Plugin\Mail\PhpMail::format.
    if (is_array($message['body'])) {
      $eol = $this->siteSettings->get('mail_line_endings', PHP_EOL);
      $message['body'] = implode("$eol$eol", $message['body']);
    }

    // Convert the content if it's HTML.
    if ($this->getMessageType($message) !== self::PLAIN) {
      $message['body'] = MailFormatHelper::htmlToText($message['body']);
    }

    $message['body'] = MailFormatHelper::wrapMail($message['body']);

    $message['body'] = [
      '#type' => 'ez_multipart_mail',
      '#plain' => Markup::create($message['body']),
      '#html' => Markup::create($html_body),
    ];

    return $message;
  }

  /**
   * Convert a plaintext message to HTML.
   *
   * @param array $message
   *   The message array.
   *
   * @return array
   *   The message converted from text/plain to text/html.
   */
  protected function messageToHtml(array $message): array {
    if ($this->getMessageType($message) === self::HTML) {
      return $message;
    }
    $key = 'Content-Type';
    foreach (array_keys($message['headers']) as $key) {
      if (strcasecmp('content-type', $key) === 0) {
        break;
      }
      $key = 'Content-Type';
    }
    $message['headers'][$key] = preg_replace('/text\/.+?;/i', 'text/html;', $message['headers'][$key]);
    if (is_array($message['body'])) {
      $eol = $this->siteSettings->get('mail_line_endings', PHP_EOL);
      $message['body'] = implode("$eol$eol", $message['body']);
    }
    $html = \Drupal::service('ez_multipart_mail.text_to_html')
      ->process($message['body']);
    if ($html !== $message['body']) {
      $message['body'] = Markup::create($html);
    }

    return $message;
  }

}
