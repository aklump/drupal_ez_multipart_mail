<?php

namespace Drupal\ez_multipart_mail\Element;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Asset\CssOptimizer;
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
        [$class, 'loadCss'],
        [$class, 'applyThemes'],
      ],
    ];
  }

  /**
   * Create the plaintext version if not exists.
   *
   * @param $element
   *
   * @return mixed
   */
  public static function ensureHasPlain(array $element) {
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
  public static function flattenArrays(array $element) {
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
  public static function filterAsNeeded(array $element) {
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
  public static function wrapMail(array $element) {
    $element['#plain'] = MailFormatHelper::wrapMail($element['#plain']);

    return $element;
  }

  /**
   * Get the CSS style tag from any attached libraries.
   *
   * @param array $element
   *
   * @return string
   *   The styles for the email <style>...
   */
  public static function getStyles(array $element): string {
    if (empty($element['#attached']['library'])) {
      return '';
    }
    $build = [];
    foreach ($element['#attached']['library'] as $library_name) {
      [$extension, $name] = explode('/', $library_name, 2);
      $definition = \Drupal::service('library.discovery')
        ->getLibraryByName($extension, $name);
      foreach ($definition['css'] as $css) {
        $css_optimizer = new CssOptimizer(\Drupal::service('file_url_generator'));
        $build[] = $css_optimizer->loadFile($css['data']);
      }
    }
    if (empty($build)) {
      return '';
    }

    return trim(implode('', $build));
  }

  public static function loadCss(array $element) {
    $element['#css'] = static::getStyles($element);

    return $element;
  }

  public static function applyThemes(array $element) {
    $child = array_diff_key($element, array_flip([
      '#pre_render',
      '#post_render',
    ]));

    $build = ['#theme' => 'ez_multipart_html'] + $child;
    $element['#html'] = \Drupal::service('renderer')
      ->renderPlain($build);

    $build = ['#theme' => 'ez_multipart_plain'] + $child;
    $element['#plain'] = \Drupal::service('renderer')
      ->renderPlain($build);

    return $element;
  }

}
