<?php

namespace Drupal\ez_multipart_mail\Element;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Site\Settings;

/**
 * Render element representing HTML + plaintext emails.
 *
 * Usage example:
 *
 * @code
 * $message['body'] = [
 *   '#type' => 'ez_multipart_mail',
 *   '#html' => \Drupal\Core\Render\Markup::create('<h1>Lorem ipsum</h1>'),
 *   '#plain' => \Drupal\Core\Render\Markup::create('Lorem ipsum'),
 * ];
 * @endcode
 *
 * @RenderElement("ez_multipart_mail")
 */
class EzMultipartMail extends RenderElement {

  public function getInfo() {
    $class = get_class($this);

    return [
      '#plain' => [],
      '#html' => [],
      '#attached' => [],
      '#pre_render' => [
        [$class, 'ensureHasPlain'],
        [$class, 'flattenArrays'],
        [$class, 'filterAsNeeded'],
        [$class, 'wrapMail'],
        [$class, 'mimeEncodeChildren'],
      ],
    ];
  }

  /**
   * Create #children as mime-encoded string.
   *
   * @param $element
   *   Fill in #children with the mime encoded message.
   *   Fill in #boundary to be used in the header.
   *
   * @return array
   *   The render array.
   */
  public static function mimeEncodeChildren($element) {
    $element['#boundary'] = uniqid('np');

    $eol = Settings::get('mail_line_endings', PHP_EOL);
    $element['#children'] = '';
    $element['#children'] .= 'This is a MIME encoded message.';
    $element['#children'] .= $eol . $eol . "--" . $element['#boundary'] . $eol;
    $element['#children'] .= "Content-Type: text/plain;charset=utf-8" . $eol . $eol;
    $element['#children'] .= $element['#plain'];
    $element['#children'] .= $eol . $eol . "--" . $element['#boundary'] . $eol;
    $element['#children'] .= "Content-Type: text/html;charset=utf-8" . $eol . $eol;
    $element['#children'] .= $element['#html'];
    $element['#children'] .= $eol . $eol . "--" . $element['#boundary'] . "--";

    return $element;
  }

  /**
   * Create the plaintext version if not exists.
   *
   * @param $element
   *
   * @return mixed
   */
  public static function ensureHasPlain($element) {
    if (empty($element['#plain'])) {
      $element['#plain'] = strval($element['#html']);
    }

    return $element;
  }

  /**
   * Flatten array bodies.
   *
   * @param array $element
   *
   * @return array
   */
  public static function flattenArrays($element) {
    $eol = Settings::get('mail_line_endings', PHP_EOL);
    $do_flatten = function (&$subject) use ($eol) {
      // This comes from \Drupal\Core\Mail\Plugin\Mail\PhpMail::format.
      if (is_array($subject)) {
        $subject = implode("$eol$eol", $subject);
      }

      return $subject;
    };
    $do_flatten($element['#plain']);
    $do_flatten($element['#html']);

    return $element;
  }

  /**
   * Filter XSS unless bodies are instances of MarkupInterface.
   *
   * @param array $element
   *
   * @return array
   */
  public static function filterAsNeeded($element) {
    if (!$element['#plain'] instanceof MarkupInterface) {
      $element['#plain'] = MailFormatHelper::htmlToText($element['#plain']);
    }
    if (!$element['#html'] instanceof MarkupInterface) {
      $element['#html'] = Xss::filter($element['#html']);
    }

    return $element;
  }

  /**
   * Wrap to mail line lengths.
   *
   * @param $element
   *
   * @return mixed
   */
  public static function wrapMail($element) {
    $element['#plain'] = MailFormatHelper::wrapMail($element['#plain']);
    $element['#html'] = MailFormatHelper::wrapMail($element['#html']);

    return $element;
  }

}
