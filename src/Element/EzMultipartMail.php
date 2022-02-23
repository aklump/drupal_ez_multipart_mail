<?php

namespace Drupal\ez_multipart_mail\Element;

use Drupal\Core\Asset\CssOptimizer;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\ez_multipart_mail\RenderHelpers;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Mail\MailFormatHelper;
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
        [$class, 'ensurePlainTextPart'],
        [$class, 'flattenArrays'],
        [$class, 'filterAndFormat'],
        [$class, 'getCssByAttached'],
        [$class, 'applyTemplates'],
      ],
    ];
  }

  /**
   * Create the plaintext version if it doesn't exist.
   *
   * @param $element
   *
   * @return mixed
   */
  public static function ensurePlainTextPart(array $element) {
    if (empty($element['#plain'])) {
      $element['#plain'] = strval($element['#html']);
    }

    return $element;
  }

  /**
   * Flatten messages if they are arrays.
   *
   * @param array $element
   *
   * @return array
   */
  public static function flattenArrays(array $element) {
    $eol = Settings::get('mail_line_endings', PHP_EOL);
    $do_flatten = function (&$subject, $key) use ($eol) {
      if (isset($subject[$key]) && is_array($subject[$key])) {
        $subject[$key] = implode("$eol$eol", $subject[$key]);
      }
    };
    $do_flatten($element, '#plain');
    $do_flatten($element, '#html');

    return $element;
  }

  /**
   * Apply filtering and formatting if not instances of MarkupInterface.
   *
   * @param array $element
   *
   * @return array
   */
  public static function filterAndFormat(array $element) {
    if (!$element['#plain'] instanceof MarkupInterface) {
      $element['#plain'] = MailFormatHelper::htmlToText($element['#plain']);
      $element['#plain'] = MailFormatHelper::formatPlain($element['#plain']);
    }
    if (!$element['#html'] instanceof MarkupInterface) {
      $element['#html'] = Xss::filter($element['#html']);
    }

    return $element;
  }

  /**
   * Add all attached CSS as #css.
   *
   * @param array $element
   *
   * @return array
   */
  public static function getCssByAttached(array $element) {
    $element['#css'] = '';
    if (!empty($element['#attached']['library'])) {
      foreach ($element['#attached']['library'] as $library_name) {
        [$extension, $name] = explode('/', $library_name, 2);
        $definition = \Drupal::service('library.discovery')
          ->getLibraryByName($extension, $name);
        foreach ($definition['css'] as $css) {
          $css_optimizer = new CssOptimizer(\Drupal::service('file_url_generator'));
          $element['#css'] .= $css_optimizer->loadFile($css['data']);
        }
      }
    }

    return $element;
  }

  /**
   * Render plain and HTML using the templates.
   *
   * @param array $element
   *
   * @return array
   */
  public static function applyTemplates(array $element) {
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
