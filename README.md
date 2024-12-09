# Easy Multipart Mail Drupal Module

## Summary

The [HTML Mail module](https://www.drupal.org/project/htmlmail) works great to send HTML mails, but it is difficult to configure it to send multipart messages due to the dependencies involved ([Mail MIME](https://www.drupal.org/project/mailmime), [Pear](https://pear.php.net/package/Mail_Mime)) . This module removes the extra effort and requires no configuration to allow for multipart messages which are HTML and plaintext. It improves the display of the plaintext email messages received by your users because they are sent as both HTML and plaintext as soon as this module is enabled.

It is not a complete multipart solution, because it doesn't support attachments.

This module provides a render element, which developers can use to generate HTML + text emails very easily at the code level. @see `\Drupal\ez_multipart_mail\Element\EzMultipartMail`. The HTML and plaintext versions of are independently themed using separate template files: _ez-multipart-html.html.twig_ and _ez-multipart-plain.html.twig_.

## Reasons to Enable this Module

* You can't or don't want to install the [Mail MIME](https://www.drupal.org/project/mailmime) module due to it's dependency on the Pear package, but you still want to send multipart MIME mails.
* As a developer, you want to send HTML emails without involving your theme.
* You need to send HTML emails where `$message['body']` has HTML that will not be filtered or touched by the mail system.
* You need to control the `text/plain` and `text/html` email parts of an email separately.

## What it Does

* Turn key solution to send HTML emails in Drupal.
* Automatically inlines CSS styles.
* Automatically converts plaintext emails to HTML (@see service `ez_multipart_mail.text_to_html`)

## Install with Composer

Because this is an unpublished, custom Drupal module, the way you install and depend on it is a little different than published, contributed modules.

* Add the following to the **root-level** _composer.json_ in the `repositories` array:
    ```json
    {
     "type": "github",
     "url": "https://github.com/aklump/drupal_ez_multipart_mail"
    }
    ```
* Add the installed directory to **root-level** _.gitignore_
  
   ```php
   /web/modules/custom/ez_multipart_mail/
   ```
* Proceed to either A or B, but not both.
---
### A. Install Standalone
* Require _ez_multipart_mail_ at the **root-level**.
    ```
    composer require drupal/ez_multipart_mail:^0.3
    ```
---
### B. Depend on This Module

(_Replace `my_module` below with your module (or theme's) real name._)

* Add the following to _my_module/composer.json_ in the `repositories` array. (_Yes, this is done both here and at the root-level._)
    ```json
    {
     "type": "github",
     "url": "https://github.com/aklump/drupal_ez_multipart_mail"
    }
    ```
* From the depending module (or theme) directory run:
    ```
    composer require drupal/ez_multipart_mail:^0.3 --no-update
    ```

* Add the following to _my_module.info.yml_ in the `dependencies` array:
    ```yaml
    drupal:ez_multipart_mail
    ```
* Back at the **root-level** run `composer update my_module`


---
### Enable This Module

* Re-build Drupal caches, if necessary.
* Enable this module, e.g.,
  ```shell
  drush pm-install ez_multipart_mail
  ```

## Quick Start

1. Configure [HTML Mail module](https://www.drupal.org/project/htmlmail) as necessary to successfully send HTML mails.
2. Do not install or enable the PEAR mime classes integration.
3. Do not enable _Provide simple plain/text alternative of the HTML mail._.
4. Post-filtering is not necessary, but may still be used.
6. Enable this module.
7. Resend your test message (/admin/config/system/htmlmail/test) and view the email source. You should see that the email is now encoded as `Content-Type: multipart/alternative`. In your email client try viewing plain, viewing formatted, and viewing source to make comparisons.

## Requirements

* [HTML Mail module](https://www.drupal.org/project/htmlmail)
* Tested on Drupal 9
* Not yet tested on Drupal 8

## Contributing

If you find this project useful... please consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fez_multipart_mail)
.

## Technically Speaking

If an email is sent with a body that is an element of type `#ez_multipart_mail` then the body will will rendered based on that element.

---
On the otherhand, if the body is an array or scalar--that is normal drupal emails--then a multipart will be built per the following...

This module will convert `text/plain` emails to `text/html` using the `ez_multipart_mail.text_to_html` service.

Then it hands the message to `\Drupal\htmlmail\Plugin\Mail\HtmlMailSystem::format()` for formatting. If that method creates a content type of `multipart/alternative` then this module returns the message.

If it remains `text/html` then it will be converted to `multipart/alternative` using the body returned from `HtmlMailSystem::format()` and a plaintext version from the original message.

## Developers: Sending Multipart Emails

### Debug Mode

If you enable the _Debug_ mode at /admin/config/system/htmlmail, when you send your emails, the Messenger service will receive the HTML and then the full body of your email. The sent email will also contain debugging info per the HTML mail module.

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

### Something is Wrapping My HTML

It's most likely _templates/ez-multipart-html.html.twig_.

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

### Plaintext emails are converting to HTML, but the HTML is wrong

* Override the service class `ez_multipart_mail.text_to_html` and modify as needed.


## Contact The Developer

* In the Loft Studios
* Aaron Klump - Web Developer
* sourcecode@intheloftstudios.com
* 360.690.6432
* PO Box 29294 Bellingham, WA 98228-1294
* <http://www.customdrupalmodules.com>
* <https://github.com/aklump>
