=== Nexi Checkout ===
Contributors: dibspayment, krokedil, NiklasHogefjord
Tags: ecommerce, woocommerce, nexi, nets easy, payment gateway
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
WC requires at least: 5.6.0
WC tested up to: 10.7.0
Stable tag: 2.14.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Accept payments via Nexi Checkout (formerly Nets Easy) in WooCommerce with cards, Vipps, MobilePay, Klarna and more.

== DESCRIPTION ==
Nexi Checkout is a plugin that extends WooCommerce, allowing you to take payments via Nets/Nexi's payment method Nexi Checkout.
Nexi Checkout (formerly known as Nets Easy checkout) online payment solution for e-commerce offers you a full embedded checkout with all popular payment methods. For more sales and less abandoned shopping carts!
Nets is part of Nexi group – for Nordic customers, the checkout is better known as Nets Easy checkout. The online payment solution from Nexi is the quickest and easiest way to increase your online sales. Up to 30% of online shoppers abandon their shopping cart because of a poor checkout experience. We help you fix that with one integration and one agreement, so you can sell more for years to come

Nexi Checkout is an exceptionally quick checkout for consumers. A single agreement for all payment methods. These are just some of the benefits to look forward to when choosing our new Nexi Checkout payment solution for your online store.

https://www.youtube.com/watch?time_continue=11&v=8ipfSYPteDI

*All-in-one* -  One agreement for all payment options including card acquiring agreements makes it easy to get started. At the moment, we offer card and invoice payments.

*Easy checkout* - Quick and mobile optimised payments for your customers with full freedom to choose payment options and the possibility of saving multiple payment cards. Returning customers also pay with just one click. Embedded in every step ensuring a smooth shopping experience.

*Easy administration* - Track sales in our user-friendly administration portal and get all payments collected in a report. It saves time in account reconsiliation and bookkeeping.

= Get started =
To get started with Nexi Checkout you need to [sign up](https://www.nets.eu/en/payments/online/) for an account.


More information on how to get started can be found in the [plugin documentation](https://docs.krokedil.com/nexi-checkout/).

= Connect Nexi Checkout to your webshop by setting up a test account. It is free and created immediately =
With a test account, you will see how the Nexi Checkout administration portal works. In the portal, you get a full overview of your payments, access to debiting, return payments and download of reports. You also get access to integration keys used when connecting your webshop to Easy. [Click here to create a test account](https://portal.dibspayment.eu/test-user-create).


== INSTALLATION	 ==
1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your /wp-content/plugins/ directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
5. Go WooCommerce Settings --> Payment Gateways and configure your Nexi Checkout settings.
6. Read more about the configuration process in the [plugin documentation](https://docs.krokedil.com/nexi-checkout/).


== Frequently Asked Questions ==
= Which countries does this payment gateway support? =
Available for merchants in Denmark, Sweden, Norway, Germany and Austria.

= Where can I find Nexi Checkout documentation? =
For help setting up and configuring Nexi Checkout please refer to our [documentation](https://docs.krokedil.com/nexi-checkout/).

= Are there any specific requirements? =
* WooCommerce 5.6.0 or newer is required.
* PHP 7.4 or higher is required.
* A SSL Certificate is required.
* This plugin integrates with Nexi Checkout. You need an agreement with Nets specific to the Nexi Checkout platform to use this plugin.

== CHANGELOG ==
= 2026-05-18    - version 2.14.4 =
* Fix           - Fixed an issue where a "Pay" button appeared on the order confirmation page for orders that had already been successfully paid.
* Fix           - Added the request URL as the 2nd parameter to the 'http_headers_useragent' filter which is required for other plugins that hook onto this filter that need the URL to modify the user agent string accordingly.

= 2026.04.21    - version 2.14.3 =
* Fix           - Fixed an issue where some settings fields were not visible on the settings page, caused by a sanitization function in our settings library.

= 2026.04.20    - version 2.14.2 =
* Enhancement   - Skipped iterating through shipping packages when no shipping method is chosen.
* Tweak         - Due to API changes, the paymentid query parameter is now used. paymentId is still supported for compatibility.
* Fix           - Fixed an issue where shipping costs were incorrectly included during the grace period for synchronized subscriptions. This caused mismatches between WooCommerce and Nexi Checkout, which would fail the payment.
* Fix           - Fixed undefined shipping method when checking for shipping at purchase completion.
* Fix           - Fixed special query characters being incorrectly encoded.

= 2026.04.08    - version 2.14.1 =
* Tweak         - Extended logging to easier track issues related to a specific payment ID.
* Fix           - The Nexi logo is now correctly displayed on the Nexi Checkout Card payment settings page.

= 2026.03.02    - version 2.14.0 =
* Feature       - Redesigned the plugin's settings page for improved structure and user experience.
* Enhancement   - Added the 'nexi_request_checkout_key' filter to allow modification of the private (checkout) key sent to Nexi.
* Tweak         - Updated the Nexi logo used in the payment methods list within WooCommerce settings.
* Fix           - Resolved an issue where error messages were not displayed when a payment failed during processing due to recent WooCommerce changes.

= 2026.02.16    - version 2.13.2 =
* Fix           - Prevented the possibility of duplicate redirects in JavaScript, ensuring that only a single order confirmation occurs.

= 2026.01.22    - version 2.13.1 =
* Fix           - Removed vendor from distignore.

= 2026.01.22    - version 2.13.0 =
* Feature       - Vipps, MobilePay, and Klarna are now available as standalone payment methods.
* Fix           - The "Payment gateway icon width" setting now works as expected.

= 2025.11.17    - version 2.12.1 =
* Fix           - Improved support for additional subscription scenarios, including failed renewals, resubscriptions, early renewals, and renewal switches.
* Fix           - Fixed a compatibility issue with certain themes, which caused the Nexi session to be terminated immediately.
* Fix           - Updated outdated documentation links to their current URLs.

= 2025.10.06    - version 2.12.0 =
* Feature       - Payments are now also terminated in Nets as the WooCommerce sessions are cleared, in cases where a new session is needed.
* Fix           - Added changelog.txt to display plugin version correctly on woocommerce.com.
* Tweak         - Renamed "Standalone payment methods" feature to "Payment method splitting".

= 2025.09.23    - version 2.11.3 =
* Fix           - The countryCode is now included in checkout requests to Nexi, so only payment options available for the customer's country are displayed.
* Tweak         - Extended logging for JSON decoding, to improve troubleshooting of unexpected API responses.

= 2025.08.12    - version 2.11.2 =
* Fix           - Fixed the checkout not always updating when adding coupons, using the inline embedded checkout flow.
* Fix           - Fixed the pay button label not being translatable in the blocks checkout.
* Tweak         - Tweaked the order confirmation priority to be compatible with the plugin "Checkout Field Editor for WooCommerce" by ThemeHigh.

= 2025.06.23    - version 2.11.1 =
* Fix           - Fixed an issue where the payment method couldn't be changed in some checkout flows after Nexi was initially selected.

= 2025.06.02    - version 2.11.0 =
* Feature       - Added a new embedded checkout flow, "Inline embedded".

= 2025.05.05    - version 2.10.4 =
* Fix           - Fixed an issue where duplicate orders could be created in rare cases.
* Fix           - Limited the max size of a log message from the frontend to 1000 characters, to prevent excessively large logs from being created.

= 2025.03.24    - version 2.10.3 =
* Fix           - Declared support for subscriptions in blocks.
* Fix           - Fixed checkout page in admin pages from blanking.
* Fix           - Removed redundant console logging.

= 2025.03.12    - version 2.10.2 =
* Tweak         - Added an admin notice for missing dependencies when needed.

= 2025.02.17    - version 2.10.1 =
* Fix           - Added missing dependencies folder.

= 2025.02.17    - version 2.10.0 =
* Feature       - Added support for the gift card plugins "Gift Cards" by Woo, "PW WooCommerce Gift Cards" by Pimwick, "YITH WooCommerce Gift Cards" by YITH, and "WooCommerce Smart Coupons" by StoreApps.
* Fix           - The overlay should now close as intended when the customer clicks on the return to store button.
* Fix           - Addressed various deprecation warnings in PHP 8.
* Fix           - The 'change_nexi_order_button_label' action filter should now work as intended.
* Tweak         - Updated assets and log name to reflect the Nexi rebranding.

= 2024.11.11    - version 2.9.4 =
* Feature       - Added the 'nexi_order_button_label' filter to allow changing the text of the payment button on block-based checkout pages.
* Feature       - Added the 'nexi_custom_payment_method_title' filter to allow changing the payment method title.
* Fix           - Fixed checkout validation failing if the phone number included hyphens without specifying a country calling code.

= 2024.10.14    - version 2.9.3 =
* Fix           - Prevented a 405 error when the payment ID is missing.
* Tweak         - Added payment method name and type to the title.
* Tweak         - Improved logging for missing payment ID.

= 2024.09.03    - version 2.9.2 =
* Fix           - The overlay modal should now close as intended when the customer clicks on the return to store button.

= 2024.07.02    - version 2.9.1 =
* Fix           - Fixed missing payment gateway icons.
* Fix           - Escape redirect URLs for redirect and overlay checkout flow.
* Tweak         - Rebranding from Nets to Nexi.
* Tweak         - Updated links related to rebranding from Nets to Nexi.

= 2024.04.17    - version 2.9.0 =
* Tweak         - Tweaks related to PHPCS & WPCS before release on woo.com.
* Fix           - PHP8.1 compatibility fix. Add missing subscription class prop (thanks @khlieng).

For the full changelog, see the [changelog on GitHub](https://github.com/krokedil/dibs-easy-for-woocommerce/blob/master/changelog.txt).
