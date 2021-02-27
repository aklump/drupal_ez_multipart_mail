                               Ez Multipart Mail

Summary

   The HTML Mail module works to send HTML mails, but it is difficult to
   configure it to send multipart messages due to the dependencies
   involved. This module removes that barrier and requires no dependencies
   and no configuration.

Quick Start

    1. Configure [1]HTML Mail module as necessary to successfully send
       HTML mails.
    2. Do not install or enable the PEAR mime classes integration.
    3. After you have it working, enable this module.
    4. Resend your test message and view the email source. You should see
       that the email is now encoded as Content-Type:
       multipart/alternative

Requirements

     * [2]HTML Mail module

Contributing

   If you find this project useful... please consider [3]making a donation
   .

Installation

    1. Install as you would any Drupal module.
    2. There are no configurations.

Technically Speaking

   This module looks at the message content type returned by
   \Drupal\htmlmail\Plugin\Mail\HtmlMailSystem::format to see if it is a
   multipart message or not. If it is, this module does nothing to the
   message. If it is not, it creates a multipart/alternative encoded
   message, which contains the un-formatted message and the formatted
   message. That's it!

Contact The Developer

   In the Loft Studios Aaron Klump - Web Developer
   sourcecode@intheloftstudios.com 360.690.6432 PO Box 29294 Bellingham,
   WA 98228-1294

   [4]http://www.customdrupalmodules.com [5]https://github.com/aklump

References

   1. https://www.drupal.org/project/htmlmail
   2. https://www.drupal.org/project/htmlmail
   3. https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4E5KZHDQCEUV8&item_name=Gratitude%20for%20aklump%2Fez_multipart_mail
   4. http://www.customdrupalmodules.com/
   5. https://github.com/aklump
