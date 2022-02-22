## Summary

The [HTML Mail module](https://www.drupal.org/project/htmlmail) works great to send HTML mails, but it is difficult to configure it to send multipart messages due to the dependencies involved ([Mail MIME](https://www.drupal.org/project/mailmime), [Pear](https://pear.php.net/package/Mail_Mime)) . This module removes the extra effort and requires no dependencies and no configuration to allow for multipart messages. It improves the display of the plaintext email messages received by your users because they are sent as both HTML and plaintext as soon as this module is enabled.

This module provides a render element, which developers can use to generate HTML + text emails very easily at the code level. @see `\Drupal\ez_multipart_mail\Element\EzMultipartMail`.

## Reasons to Enable this Module

* You can't or don't want to install the [Mail MIME](https://www.drupal.org/project/mailmime) module due to it's dependency on the Pear package, but you still want to send multipart MIME mails.
* As a developer, you want to send HTML emails without involving the theme system.
* You need to send HTML emails where `$message['body']` has HTML that will not be filtered or touched by the mail system.
* You need to control plain and HTML versions separately.
* You want to send HTML emails without involving the theme system.

## Quick Start

1. Configure [HTML Mail module](https://www.drupal.org/project/htmlmail) as necessary to successfully send HTML mails.
1. Do not install or enable the PEAR mime classes integration.
1. After you have it working, enable this module.
1. Resend your test message (/admin/config/system/htmlmail/test) and view the email source. You should see that the email is now encoded as `Content-Type: multipart/alternative`. In your email client try viewing plain, viewing formatted, and viewing source to make comparisons.

## Requirements

* [HTML Mail module](https://www.drupal.org/project/htmlmail)

## Contributing

If you find this project useful... please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fez_multipart_mail)
.

## Installation

1. Install as you would any Drupal module.
1. There are no configurations.

## Technically Speaking

This module looks at the message content type returned by `\Drupal\htmlmail\Plugin\Mail\HtmlMailSystem::format` to see if it is a multipart message or not. If it is, this module does nothing to the message. If it is not, it creates a `multipart/alternative` encoded message, which contains the un-formatted message and the formatted message. That's it!

## Developers: Sending Multipart Emails

### I want to send an email with html body, no templates or theme wrappers.

The answer is to set the `$message['body']` like this:

```php
$message['body'] = [
  '#type' => 'ez_multipart_mail',
  '#html' => \Drupal\Core\Render\Markup::create('<h1>Lorem</h1>'),
  '#plain' => \Drupal\Core\Render\Markup::create('# Lorem'),
];
```

You can see that you have absolute control of both HTML and plain. Also by using `Markup::create()` you ensure no filtering will take place.

#### Other Variations

```php
<?php

// This HTML will be filtered using \Drupal\Component\Utility\Xss::filter().
// The plaintext will be generated using \Drupal\Core\Mail\MailFormatHelper::htmlToText().
$message['body'] = [
  '#type' => 'ez_multipart_mail',
  '#html' => '<h1>Lorem</h1>',
];


// This HTML will not be filtered.
// The plaintext will be generated using \Drupal\Core\Mail\MailFormatHelper::htmlToText().
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
```

### I Need to include CSS

Create a library to contain your CSS. The CSS will be added to the email.

```php
$message['body'] = [
  '#type' => 'ez_multipart_mail',
  '#html' => \Drupal\Core\Render\Markup::create('<h1>Lorem</h1>'),
  '#plain' => \Drupal\Core\Render\Markup::create('# Lorem'),
  '#attached' => ['library' => 'foo/bar'],
];
```

### I've enabled the module, but my mail is not HTML

* Are you using the `ez_multipart_mail` render element as `$message['body']`?
* Is `#html` an instance of `\Drupal\Component\Render\MarkupInterface`?
* Can the recipient receive HTML emails per `\Drupal\htmlmail\Helper\HtmlMailHelper::htmlMailIsAllowed()`?
