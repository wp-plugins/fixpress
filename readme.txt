=== FixPress ===
Contributors:  pross
Author URI:  http://www.pross.org.uk
Plugin URL:  http://www.pross.org.uk/plugins
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=pross2%40gmail%2ecom&item_name=Simon%20Prosser&page_style=PayPal&no_shipping=0&return=http%3a%2f%2fwww%2epross%2eorg%2euk&no_note=1&tax=0&currency_code=GBP&lc=GB&bn=PP%2dDonationsBF&charset=UTF%2d8
Requires at Least:  3.0
Tested Up To: 3.0.1
Tags:  template, gallery, comments, media, embed, oembed, fix, XHTML, html5

== Description ==

Heres a simple plugin that fixes the gallery so it validates by pushing the css into `<head>` and a couple of other little tweaks.
Also fixes the comment form by removing the 'aria' bits that wont validate as XHTML.

Now with youtube and googlevideo fixes!

== Installation ==

1. upload the contents of the zip file to your `wp-content/plugins` directory
1. go to the Plugins main menu and find `FixPress`, then click Activate


== Changelog ==

= 0.8 =
* Another video fix ;) HTML5 and XHTML both validate now.

= 0.7 = 
* Fixed youtube links AGAIN
  Upload the css folder, so gallery will now work!

= 0.5 =
* Finally fixed youtube links! XHTML Validated now.

= 0.4 =
* Fixed Stupid bug in the googlevideo code, added priority to load before default filter.

= 0.3 =
* Added youtube and googlevideo hacks from trac to fix video validation.

= 0.2 =
* Added define for autodiscovery.

= 0.1 =
* First release.