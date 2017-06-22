=== DIBS Easy for WooCommerce ===
Contributors: dibspayment, krokedil, NiklasHogefjord
Tags: ecommerce, e-commerce, woocommerce, dibs, easy
Requires at least: 4.7
Tested up to: 4.8
Requires WooCommerce at least: 3.0
Tested WooCommerce up to: 3.0.8
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

DIBS Easy for WooCommerce is a plugin that extends WooCommerce, allowing you to take payments via DIBS new payment method DIBS Easy.



== DESCRIPTION ==
To get started with DIBS Easy you need to [sign up](http://www.dibs.se/easy-se) for an account.

More information on how to get started can be found in the [plugin documentation](http://docs.krokedil.com/documentation/dibs-easy-for-woocommerce/).



== INSTALLATION	 ==
1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go WooCommerce Settings --> Payment Gateways and configure your DIBS Easy settings.
6. Read more about the configuration process in the [plugin documentation](http://docs.krokedil.com/documentation/dibs-easy-for-woocommerce/).


== CHANGELOG ==

= 2017.06.22  	- version 0.3.2 =
* Added     	- Debug logging to catch all requests.
* Fix       	- Changed populate_fields to only make one call.

= 2017.06.08  	- version 0.3.1 =
* Tweak			- Flatsome theme compatibility - remove blue rectangle in checkout if DIBS is the selected payment method.
* Fix			- Send SKU instead of product id as reference to DIBS.
* Fix			- PHP notices.

= 2017.05.31  	- version 0.3.0 =
* Tweak			- Make all WC checkout forms not required if using DIBS Easy.
* Fix			- Don't display standard billing fields on initial checkout pageload.
* Fix			- Check terms checkbox (if it exist) before submitting the WC form.
* Fix			- Customer order note saved correctly even when redirected to 3DSecure window.
* Fix			- Move customer order note textarea field bug fix.


= 2017.05.25  	- version 0.2.0 =
* Tweak			- Added automatic updates via WordPress admin.
* Tweak			- Add error notice in cancel order page (cart page) if purchase wasn’t approved in 3DSecure.

= 2017.05.22  	- version 0.1.0-beta =