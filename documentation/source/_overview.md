## Summary

The [HTML Mail module](https://www.drupal.org/project/htmlmail) works great to
send HTML mails, but it is difficult to configure it to send multipart messages
due to the dependencies involved. This module removes the extra effort and
requires no dependencies and no configuration. It improves the display of the
plaintext email messages received by your users.

## Quick Start

1. Configure [HTML Mail module](https://www.drupal.org/project/htmlmail) as
   necessary to successfully send HTML mails.
1. Do not install or enable the PEAR mime classes integration.
1. After you have it working, enable this module.
1. Resend your test message (/admin/config/system/htmlmail/test) and view the
   email source. You should see that the email is now encoded
   as `Content-Type: multipart/alternative`. In your email client try viewing
   plain, viewing formatted, and viewing source to make comparisons.

## Requirements

* [HTML Mail module](https://www.drupal.org/project/htmlmail)

## Contributing

If you find this project useful... please
consider [making a donation](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fez_multipart_mail)
.

## Installation

1. Install as you would any Drupal module.
1. There are no configurations.

## Technically Speaking

This module looks at the message content type returned
by `\Drupal\htmlmail\Plugin\Mail\HtmlMailSystem::format` to see if it is a
multipart message or not. If it is, this module does nothing to the message. If
it is not, it creates a `multipart/alternative` encoded message, which contains
the un-formatted message and the formatted message. That's it!
