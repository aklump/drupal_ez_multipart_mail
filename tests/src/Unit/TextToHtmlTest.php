<?php


namespace Drupal\Tests\ez_multipart_mail\Unit;

use PHPUnit\Framework\TestCase;
use Drupal\ez_multipart_mail\TextToHtml;

/**
 * @group extensions
 * @group ez_multipart_mail
 * @coversDefaultClass \Drupal\ez_multipart_mail\TextToHtml
 */
final class TextToHtmlTest extends TestCase {

  /**
   * Provides data for testProcess.
   */
  public function dataForTestProcessProvider() {
    $tests = [];
    $tests[] = [
      "Lorem ipsum dolor sit https://www.google.com ex ea commodo consequat.\n\nDuis aute irure dolor in reprehenderit in voluptate.",
      "<p>Lorem ipsum dolor sit <a href=\"https://www.google.com\">google.com</a> ex ea commodo consequat.</p>\n<p>Duis aute irure dolor in reprehenderit in voluptate.</p>",
    ];
    $tests[] = [
      "lorem me@you.com foo",
      'lorem <a href="mailto:me@you.com">me@you.com</a> foo',
    ];
    $tests[] = [
      "http://apple.com",
      '<a href="http://apple.com">apple.com</a>',
    ];
    $tests[] = [
      "https://www.google.com",
      '<a href="https://www.google.com">google.com</a>',
    ];
    $tests[] = [
      "foo\nbar",
      "foo<br/>\nbar",
    ];
    $tests[] = [
      "foo",
      "foo",
    ];
    $tests[] = [
      "foo\n\nbar",
      "<p>foo</p>\n<p>bar</p>",
    ];

    return $tests;
  }

  /**
   * @dataProvider dataForTestProcessProvider
   */
  public function testProcess($plain, $control) {
    $service = new TextToHtml();
    $html = $service->process($plain);
    $this->assertSame($control, $html);
  }

}
