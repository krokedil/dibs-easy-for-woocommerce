=== DIBS Easy for WooCommerce ===
Contributors: dibspayment, krokedil, NiklasHogefjord
Tags: ecommerce, e-commerce, woocommerce, dibs, easy
Requires at least: 4.7
Tested up to: 4.8.2
Stable tag: trunk
Requires WooCommerce at least: 3.0
Tested WooCommerce up to: 3.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html


== DESCRIPTION ==
DIBS Easy for WooCommerce is a plugin that extends WooCommerce, allowing you to take payments via DIBS new payment method Easy.

Easy is an exceptionally quick checkout for consumers. A single agreement for all payment methods. These are just some of the benefits to look forward to when choosing our new Easy payment solution for your online store.

https://www.youtube.com/watch?time_continue=11&v=8ipfSYPteDI

*All-in-one* - One agreement for all payment options including card acquiring agreements makes it easy to get started. At the moment, we offer card payments and in the autumn, invoice payments will also be added.

*Easy checkout* - Quick and mobile optimised payments for your customers with full freedom to choose payment options and the possibility of saving multiple payment cards. Returning customers also pay with just one click. Embedded in every step ensuring a smooth shopping experience.      

*Easy administration* - Track sales in our user-friendly administration portal and get all payments collected in a report. It saves time in account reconsiliation and bookkeeping.

= Get started =
To get started with DIBS Easy you need to [sign up](http://www.dibs.se/easy-se) for an account.

More information on how to get started can be found in the [plugin documentation](http://docs.krokedil.com/documentation/dibs-easy-for-woocommerce/).

= Connect Easy to your webshop by setting up a test account. It is free and created immediately =
With a test account, you will see how the Easy administration portal works. In the portal, you get a full overview of your payments, access to debiting, return payments and download of reports. You also get access to integration keys used when connecting your webshop to Easy. [Click here to create a test account](https://portal.dibspayment.eu/test-user-create).  


== INSTALLATION	 ==
1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go WooCommerce Settings --> Payment Gateways and configure your DIBS Easy settings.
6. Read more about the configuration process in the [plugin documentation](http://docs.krokedil.com/documentation/dibs-easy-for-woocommerce/).


== Frequently Asked Questions ==
= Which countries does this payment gateway support? =
At the moment it's only available for merchants in Sweden.

= Where can I find DIBS Easy for WooCommerce documentation? =
For help setting up and configuring DIBS Easy for WooCommerce please refer to our [documentation](http://docs.krokedil.com/documentation/dibs-easy-for-woocommerce/).



== CHANGELOG ==

= 2017.10.18  	- version 1.0.3 =
* Feature		- Added support for Norwegian and Danish locale.
* Fix			- Save masked card number in WC order in direct payment flow (purchases with no redirect to 3D Secure).

= 2017.10.13  	- version 1.0.2 =
* Fix       	- Set Set DIBS Easy as the chosen payment method when retrieving payment id from DIBS (to be able to handel the checkout process better when Easy isn't the default payment method).

= 2017.08.25  	- version 1.0.1 =
* Fix       	- Fixed a bug where invalid characters could be sent (in product name) to DIBS Easy API.
* Fix			- Error messaging improvements in console.log on checkout page.

= 2017.07.29  	- version 1.0.0 =
* Tweak			- First release on wordpress.org.
* Fix			- Added helper functions to convert country codes. Makes it possible to take international purchases.

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