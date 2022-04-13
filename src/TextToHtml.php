<?php

namespace Drupal\ez_multipart_mail;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Markup;

/**
 * A class to convert plaintext to HTML.
 *
 * @see \Drupal\ez_multipart_mail\Plugin\Mail\EasyMultipartMailFormatter::messageToHtml()
 */
class TextToHtml {

  public function process(string $plaintext): string {

    // Replace line breaks with html breaks.
    $html = preg_replace('/\r\n|\n/', "<br/>\n", $plaintext);
    if ($plaintext !== $html) {

      // Convert double HTML breaks to paragraphs.
      $paragraphs = preg_split('/\s*<br\/>\s*<br\/>\s*/', $html);
      if (count($paragraphs) > 1) {
        $html = '<p>' . implode("</p>\n<p>", $paragraphs) . '</p>';
      }
    }

    $html = $this->processLinks($html);
    $html = $this->processEmails($html);

    return $html;
  }

  private function processLinks(string $subject) {
    // @link https://ihateregex.io/expr/url/
    $regex = '/https?:\/\/(www\.)?([-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()!@:%_\+.~#?&\/\/=]*))/';

    return preg_replace_callback($regex, function ($link) {
      return sprintf('<a href="%s">%s</a>', $link[0], $link[2]);
    }, $subject);
  }

  private function processEmails(string $subject) {
    $regex = '/(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))/';

    return preg_replace_callback($regex, function ($link) {
      return sprintf('<a href="mailto:%s">%s</a>', $link[0], $link[0]);
    }, $subject);
  }

}
