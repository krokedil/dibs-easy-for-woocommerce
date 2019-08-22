=== DIBS Easy for WooCommerce ===
Contributors: dibspayment, krokedil, NiklasHogefjord
Tags: ecommerce, e-commerce, woocommerce, dibs, easy
Requires at least: 4.7
Tested up to: 5.2.2
Stable tag: trunk
Requires WooCommerce at least: 3.0
Tested WooCommerce up to: 3.7.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html


== DESCRIPTION ==
DIBS Easy for WooCommerce is a plugin that extends WooCommerce, allowing you to take payments via DIBS new payment method Easy.

Easy is an exceptionally quick checkout for consumers. A single agreement for all payment methods. These are just some of the benefits to look forward to when choosing our new Easy payment solution for your online store.

https://www.youtube.com/watch?time_continue=11&v=8ipfSYPteDI

*All-in-one* -  One agreement for all payment options including card acquiring agreements makes it easy to get started. At the moment, we offer card and invoice payments.

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
Available for merchants in Denmark, Sweden and Norway.

= Where can I find DIBS Easy for WooCommerce documentation? =
For help setting up and configuring DIBS Easy for WooCommerce please refer to our [documentation](http://docs.krokedil.com/documentation/dibs-easy-for-woocommerce/).

= Are there any specific requirements? =
* WooCommerce 3.0 or newer is required.
* PHP 5.6 or higher is required.
* A SSL Certificate is required.
* This plugin integrates with DIBS Easy. You need an agreement with DIBS specific to the Easy platform to use this plugin.

== CHANGELOG ==

= 2019.08.22    - version 1.10.3 =
* Tweak         - Remove checkout_error order creation code. Not used anymore (since of version 1.10.0).
* Tweak         - Dont send web hooks to DIBS if host is local.
* Fix           - Don't try to update order reference in DIBS (in process_dibs_payment_in_order()) if the checkout flow is Redirect.
* Fix           - Improved set_order_status() function in API callback. Save _dibs_date_paid, dibs_payment_type & dibs_payment_method.

= 2019.08.07    - version 1.10.2 =
* Fix           - Avoid notices/headers already sent issue in admin notice if plugin settings doesn't exist yet.

= 2019.08.07    - version 1.10.1 =
* Tweak         - Added message to order note if recurring payment fails.
* Tweak         - Only display admin notice about recommended account settings if Embedded is the selected checkout flow.
* Fix           - Updating nonce correctly on update_checkout. This could cause issues finalizing order if logging in on checkout page.
* Fix           - Create new subscription id in DIBS if customer use Easy as payment method on manual renewal (for example if the card did expire for the old subscription id).
* Fix           - Remove - from phone numbers sent to DIBS.
* Fix           - Avoid rounding issues (on prices sent to DIBS) that can happen occationally.
* Fix           - Prevent looping through null to stop JS errors (in handling of extra checkout fields logic).


= 2019.06.19    - version 1.10.0 =
* Tweak         - Changed logic for embedded checkout flow. Order now created on pay-initialized event (when customer clicks pay button), before redirect to 3DSecure. WooCommerce now handle the validation logic.
* Tweak         - Order management improvements. Don't try to make activate/cancel request to DIBS if order hasn't the correct status in Woo.
* Fix           - Fix in order totals comparison check, in check_order_status function during callback from DIBS.

= 2019.06.07    - version 1.9.1 =
* Fix           - Save _dibs_payment_id correct on orders created via checkout error sequence.

= 2019.06.03    - version 1.9.0 =
* Feature       - Introduce redirect to DIBS hosted payment window checkout flow. Can be changed in settings under "Checkout flow".
* Tweak         - Remove custom order status functionality (not used anymore).
* Fix           - Tweaks to API requests so that filter dibs_easy_request_secret_key can be used throughout the entire order process.
* Fix           - Limit product names to 128 characters sent to DIBS.

= 2019.05.23    - version 1.8.3 =
* Fix           - Redirect customer to order received page during payment-completed event if paymentID already exist in an order.
* Fix           - Save paymentID as post meta _dibs_payment_id in backup_order_creation.
* Fix           - Fix PHP warning in order submission failure.

= 2019.05.23    - version 1.8.2 =
* Enhancement   - Create order lines in backup order creation process. Makes it possible to trigger payment_complete() even if something goes wrong during regular checkout process.
* Fix           - Avoid division by zero in tax rate calculation.

= 2019.05.17    - version 1.8.1 =
* Tweak         - Logging improvements during checkout form submission.

= 2019.05.13    - version 1.8.0 =
* Feature       - Added support for extra checkout field validation (checkout form fields outside of Easy checkout). Read more about it here: https://docs.krokedil.com/article/277-dibs-easy-extra-checkout-fields
* Tweak         - Improved logging.
* Fix           - Fixed PHP warnings.
* Fix           - Remove old code that prevented displaying of cancel/error message when redirected back from 3dsecure.

= 2019.05.01    - version 1.7.5 =
* Tweak         - Added listener for pay-initialized + added JS event dibs_pay_initialized.
* Tweak         - Change callback listener for order from payment.reservation.created to payment.checkout.completed webhook (because of Swish intoduction).
* Tweak         - Order management: Don't try to make a charge request if payment_type is A2A (Swish and other account 2 account purchases).
* Tweak         - Order management: Don't try to charge if we already have a charge ID.
* Tweak         - Order management: Make sure we add an order note if charge fails.
* Tweak         - Order management: Set order status to On hold if charge fails (the first time). If trying to activate the order again, Woo order status will be set to Completed.
* Tweak         - Order management: Added filter dibs_easy_failed_charge_status so other plugins can change the status the Woo order is set to if charge request fails.

= 2019.04.10    - version 1.7.4 =
* Tweak         - Added filters dibs_easy_request_checkout_key & dibs_easy_request_secret_key to be able to modify merchant ID sent to DIBS.
* Fix           - Added check for chargedAmount when completing payment in Woo. Fixes so order status is set to Processing with Swish payments.

= 2019.03.26    - version 1.7.3 =
* Tweak         - Added payment gateway icon.
* Tweak         - Added filter wc_dibs_easy_icon_html so payment gateway icon can be customized.
* Fix           - Fixed PHP notices in get_invoice_fees() function.

= 2019.03.14    - version 1.7.2 =
* Tweak         - Updated URL to docs.
* Fix           - Tax fix in invoice fee handling.

= 2019.02.19    - version 1.7.1 =
* Fix           - Version number bump. One file not comitted properly to wp.org in version 1.7.0.

= 2019.02.19    - version 1.7.0 =
* Feature       - Functionality for adding invoice fee to order.
* Tweak         - Send correct product name to DIBS for variable products.

= 2019.02.06    - version 1.6.4 =
* Tweak         - Template update - hide checkout if user not valid (not logged in & gest checkout is disabled).
* Tweak         - Add custom user-agent in requests to DIBS.
* Tweak         - Use $order->get_transaction_id() instead of _dibs_payment_id in charge request to DIBS.
* Fix           - Improved user agent info for domains with å ä ö (could cause error in DIBS system).

= 2018.12.06    - version 1.6.3 =
* Tweak			- Improved error messaging in failed subscription renewal process.
* Fix			- Send cart item unit price excl vat to DIBS.
* Fix			- Fix netTotalAmount on fees sent to DIBS.
* Fix			- Don’t run plugin if WooCommerce isn’t activated.

= 2018.12.03    - version 1.6.2 =
* Tweak			- Added WooEasyKrokedil as commercePlatformTag in header sent to DIBS.
* Tweak			- Plugin WordPress 5.0 compatible.
* Fix			- Updated how available shipping countries are sent to DIBS to reflect DIBS API changes.
* Fix			- Do not limit number of shipping countries to 5. No limits in DIBS API anymore. 
* Fix			- Improved handling of allowed characters in product names sent to DIBS. Also added unicode handler.

= 2018.11.21    - version 1.6.1 =
* Fix			- Bug fix in logic for user must login message (after customer_adress_updated event has been triggered).
* Fix			- Extended wc_dibs_clean_name to allow èÈéÉ. Caused Easy Checkout not to be rendered.
* Fix			- Change console.table to console.log in js-file (potential conflict with IE).

= 2018.11.07    - version 1.6.0 =
* Feature		- Add support for recurring payments via WooCommerce Subscriptions.
* Feature		- Create a WC order (with order status Failed) in webhook/API callback from DIBS if DIBS paymentId doesn't exist in any Woo order.
* Tweak			- Catch and print error message better if update cart fails.
* Tweak			- Inform existing customer that he/she must login if guest checkout isn't enabled in Woo.
* Tweak			- Code cleaning.
* Tweak			- Finalize order in Woo earlier (in process_payment instead of woocommerce_thankyou).
* Tweak			- Updated POT-file & Swedish translation.
* Fix			- Don't display DIBS Easy template if cart doesn’t needs_payment().

= 2018.10.31    - version 1.5.5 =
* Tweak			- Change so WC order is created after DIBS payment reservation is created.
* Tweak			- Code cleaning.
* Tweak			- Return detailed error message as order note if Cancel order doesn’t work.
* Fix			- Avoids creation of double orders in some stores.

= 2018.10.29    - version 1.5.4 =
* Tweak			- Improved messaging and handling of order status if order activate & cancel request was denied from DIBS.
* Tweak			- Change plugin version constant name to WC_DIBS_EASY_VERSION (conflicted with D2 plugin).
* Tweak			- Add get_order_number function and check for Sequential order numbers plugin features.
* Fix			- Improved error message handling in communication with DIBS.
* Fix			- Don't try to send shipping item row if no shipping is available. Caused Easy Checkout not to be rendered.
* Fix			- Extended wc_dibs_clean_name to allow ØÆøæ. Caused Easy Checkout not to be rendered.
* Fix			- Revert ajax_on_checkout_error function to better handle order completion when regular Woo checkout submission fails.

= 2018.10.23    - version 1.5.3 =
* Fix			- Fixed issue where first shipping method always was set as order shipping in some stores.

= 2018.10.22    - version 1.5.2 =
* Tweak			- Update _cart_hash in Woo order in filter woocommerce_create_order (to avoid double orders).
* Fix			- Added function to filter order line names (to remove invalid characters in DIBS system).

= 2018.10.22    - version 1.5.1 =
* Tweak			- Add plugin version number when enqueuing style.css file.
* Fix			- Fixed rounding issue that could cause order total mismatch between DIBS & Woo and by that generate double orders in Woo.
* Fix			- Fix PHP notice in get_error_message function.

= 2018.10.19    - version 1.5.0 =
* Tweak			- Rewrite of request classes used for communication between Woo and DIBS.
* Tweak			- Don't create order in Woo until customer have identified herself in Easy checkout (on DIBS address-changed JS event).
* Tweak			- Send Woo order number to DIBS via their update reference endpoint.
* Tweak			- Improved error message response on checkout page if something is wrong with create Payment ID request.
* Tweak			- Added checkout form processing modal with a message that the customer should wait until the process has been finalized.
* fix			- Changes to avoid duplicate orders during checkout form processing in Woo.
* Fix			- Added fix for double order_comment fields causing js error.
* Fix			- Make sure all prices are sent as integers.
* Fix			- PHP notice fix. 

= 2018.09.04    - version 1.4.2 =
* Tweak			- Added fees when sending order lines to DIBS.

= 2018.09.04    - version 1.4.1
* Tweak			- Plugin now requires https.
* Tweak			- Added admin notice if https is note set in store.
* Tweak			- Added WooCommerce account settings check. To avoid issues during finalizing of checkout form submission.
* Fix			- Only allow payment method to be available is currency is DKK, NOK or SEK. 

= 2018.08.15    - version 1.4.0 =
* Feature 		- Added support for listening to DIBS shipping update event (possibility to update shipping methods/shipping depending on entered customer data in Easy iframe).
* Feature       - Use template file for displaying DIBS Easy. Making it possible to overwrite via theme.
* Feature 		- Added support for B2B purchases.
* Enhancement	- Added support for DIBS webhooks (API callbacks for payment.reservation.created). Now scheduling check of order status 2 minutes after purchase completed.
* Tweak			- Improved messaging (saved as an order note) on order submission failure.
* Tweak 		- Ajax functionality now extending WC_Ajax class.
* Tweak 		- Logging enhancements.
* Fix 			- wc_maybe_define_constant WOOCOMMERCE_CHECKOUT in ajax functions.
* Fix 			- Delete dibs sessions for all orders if they exist (even if order is finalized in Woo w. another payment method).

= 2018.03.16    - version 1.3.0 =
* Feature       - Added support for ShippingCountries (possible to add up to 5 specific countries that the e-commerce store ship to).
* Tweak         - Save DIBS cusotmer data addressLine2 in billing_address_2 & shipping_address_2 in WC if it exist in order.

= 2018.01.15    - version 1.2.0 =
* Feature		- Added termsUrl sent to DIBS (using WooCommerce terms & conditions page).
* Tweak			- Added Admin notices class to inform merchant if no terms page is set in WooCommerce settings.

= 2017.12.13    - version 1.1.1 =
* Fix           - Better handling of failed/canceled card payments when customer is redirected back to checkout from 3DSecure window.

= 2017.12.07    - version 1.1.0 =
* Tweak         - Adds support for order submission failure handling.
* Tweak         - Increased timeout to 10 seconds when communicating with DIBS.
* Fix           - Fallback to be able to process order even if DIBS doesn't respond on our call after payment sucess.

= 2017.12.05    - version 1.0.8 =
* Fix		    - Improved how checkout fields are set as not required by hooking into filter woocommerce_checkout_posted_data.

= 2017.11.30    - version 1.0.7 =
* Fix		    - Change how WC checkout fields are set as not required if DIBS Easy is the selected payment gateway.

= 2017.11.29    - version 1.0.6 =
* Fix		    - Prevent order status to be changed to Pending and back to Processing if thankyou page is reloaded and sessions aren't deleted properly.

= 2017.11.28  	- version 1.0.5 =
* Tweak		    - Updated SKU function to get variable ID if variable SKU is missing but parent product has SKU
* Fix		    - Adds shipping address to prepopulated fields before submitting form.

= 2017.11.18  	- version 1.0.4 =
* Tweak			- Adds plugin action links (to settings and docs).
* Tweak			- Updated settings labels.

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