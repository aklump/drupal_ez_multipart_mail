                       Easy Multipart Mail Drupal Module

Summary

   The [1]HTML Mail module works great to send HTML mails, but it is
   difficult to configure it to send multipart messages due to the
   dependencies involved ([2]Mail MIME, [3]Pear) . This module removes the
   extra effort and requires no dependencies and no configuration to allow
   for multipart messages which are HTML and plaintext. It improves the
   display of the plaintext email messages received by your users because
   they are sent as both HTML and plaintext as soon as this module is
   enabled.

   It is not a complete multipart solution, because it doesn't support
   attachments.

   This module provides a render element, which developers can use to
   generate HTML + text emails very easily at the code level. @see
   \Drupal\ez_multipart_mail\Element\EzMultipartMail. The HTML and
   plaintext versions of are independently themed using separate templates
   files: ez-multipart-html.html.twig and ez-multipart-plain.html.twig.

Reasons to Enable this Module

     * You can't or don't want to install the [4]Mail MIME module due to
       it's dependency on the Pear package, but you still want to send
       multipart MIME mails.
     * As a developer, you want to send HTML emails without involving your
       theme.
     * You need to send HTML emails where $message['body'] has HTML that
       will not be filtered or touched by the mail system.
     * You need to control plain and HTML versions separately.

What it Does

     * Turn key solution to send HTML emails in Drupal.
     * Automatically inlines CSS styles.

Quick Start

    1. Configure [5]HTML Mail module as necessary to successfully send
       HTML mails.
    2. Do not install or enable the PEAR mime classes integration.
    3. Add the following to the root-level composer.json and then call
       composer require drupal/ez_multipart_mail:@dev: json {
       "repositories": [ { "type": "path", "url":
       "web/modules/custom/ez_multipart_mail" } ] }
    4. Enable this module.
    5. Resend your test message (/admin/config/system/htmlmail/test) and
       view the email source. You should see that the email is now encoded
       as Content-Type: multipart/alternative. In your email client try
       viewing plain, viewing formatted, and viewing source to make
       comparisons.

Requirements

     * [6]HTML Mail module
     * Tested on Drupal 9
     * Not yet tested on Drupal 8

Contributing

   If you find this project useful... please consider [7]making a donation
   .

Installation

    1. Save this module to web/modules/custom/.
    2. Add the following to root-level composer.json, repositories json {
       "type": "path", "url": "web/modules/custom/ez_multipart_mail" }
    3. Run composer require drupal/ez_multipart_mail:@dev
    4. Enable the module.

Technically Speaking

   This module looks at the message content type returned by
   \Drupal\htmlmail\Plugin\Mail\HtmlMailSystem::format to see if it is a
   multipart message or not. If it is, this module does nothing to the
   message. If it is not, it creates a multipart/alternative encoded
   message, which contains the un-formatted message and the formatted
   message. That's it!

Developers: Sending Multipart Emails

  Debug Mode

   If you enable the Debug mode at /admin/config/system/htmlmail, when you
   try to send your emails, the HTML version will be rendered in your
   browser with the exact markup that will be sent in the email, including
   inlined-styles, etc. Use this to design and build your emails.

  I want to send an email with html body, no templates or theme wrappers.

   The answer is to set the $message['body'] like this:
$message['body'] = [
  '#type' => 'ez_multipart_mail',
  '#html' => \Drupal\Core\Render\Markup::create('<h1>Lorem</h1>'),
  '#plain' => \Drupal\Core\Render\Markup::create('# Lorem'),
];

   You can see that you have absolute control of both HTML and plain. Also
   by using Markup::create() you ensure no filtering will take place.

  Something is Wrapping My HTML

   That would be templates/ez-multipart-html.html.twig.

    Other Variations

<?php

// This HTML will be filtered using \Drupal\Component\Utility\Xss::filter().
// The plaintext will be generated using \Drupal\Core\Mail\MailFormatHelper::htm
lToText().
$message['body'] = [
  '#type' => 'ez_multipart_mail',
  '#html' => '<h1>Lorem</h1>',
];


// This HTML will not be filtered.
// The plaintext will be generated using \Drupal\Core\Mail\MailFormatHelper::htm
lToText().
$message['body'] = [
  '#type' => 'ez_multipart_mail',
  '#html' => \Drupal\Core\Render\Markup::create('<h1>Lorem</h1>'),
];

// This HTML will not be filtered.
// The plaintext array will be treated as separate paragraphs.
$message['body'] = [
  '#type' => 'ez_multipart_mail',
  '#html' => \Drupal\Core\Render\Markup::create('<h1>Lorem</h1>'),
  '#plain' => ['Lorem', 'ipsum', 'dolar'],
];

  I Need to include CSS

   Create a library to contain your CSS. The CSS will be added to the
   email.
$message['body'] = [
  '#type' => 'ez_multipart_mail',
  '#html' => \Drupal\Core\Render\Markup::create('<h1>Lorem</h1>'),
  '#plain' => \Drupal\Core\Render\Markup::create('# Lorem'),
  '#attached' => ['library' => 'foo/bar'],
];

  I've enabled the module, but my mail is not HTML

     * Are you using the ez_multipart_mail render element as
       $message['body']?
     * Is #html an instance of \Drupal\Component\Render\MarkupInterface?
     * Can the recipient receive HTML emails per
       \Drupal\htmlmail\Helper\HtmlMailHelper::htmlMailIsAllowed()?

Contact The Developer

     * In the Loft Studios
     * Aaron Klump - Web Developer
     * sourcecode@intheloftstudios.com
     * 360.690.6432
     * PO Box 29294 Bellingham, WA 98228-1294
     * [8]http://www.customdrupalmodules.com
     * [9]https://github.com/aklump

References

   1. https://www.drupal.org/project/htmlmail
   2. https://www.drupal.org/project/mailmime
   3. https://pear.php.net/package/Mail_Mime
   4. https://www.drupal.org/project/mailmime
   5. https://www.drupal.org/project/htmlmail
   6. https://www.drupal.org/project/htmlmail
   7. https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fez_multipart_mail
   8. http://www.customdrupalmodules.com/
   9. https://github.com/aklump
