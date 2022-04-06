<?php

namespace Drupal\ez_multipart_mail;

use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;

final class Debugger implements MailInterface {

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    $type = 1;

    // The decoded HTML parts.
    $html = $this->getHtmlParts($message);
    foreach ($html as $item) {
      $this->messenger->addMessage($item, "debug$type");
      ++$type;
    }

    // The raw source.
    $this->messenger->addMessage(sprintf("<pre>%s</pre>", $message['body']), "debug$type");

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    return TRUE;
  }

  /**
   * @param array $message
   *   A multi-part mime message.
   *
   * @return \Drupal\Core\Render\Markup[]
   *   An array of decided HTML parts.
   */
  private function getHtmlParts(array $message): array {
    preg_match('/boundary=(.+)"/', json_encode($message['headers']), $matches);
    $boundary = $matches[1];
    if (!$boundary) {
      return [];
    }
    $parts = explode("--$boundary", $message['body']);
    $html_parts = [];
    foreach ($parts as $part) {
      $part = trim($part);
      if (!stristr($part, 'Content-type: text/html')) {
        continue;
      }
      list($headers, $message) = explode("\r\n\r\n", $part);

      if (strstr($headers, "base64")) {
        $message = base64_decode($message);
      }
      $html_parts[] = Markup::create($message);
    }

    return $html_parts;
  }
}
