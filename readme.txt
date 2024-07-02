=== Nexi Checkout ===
Contributors: dibspayment, krokedil, NiklasHogefjord
Tags: ecommerce, e-commerce, woocommerce, dibs, nets easy, nets, nexi
Requires at least: 5.0
Tested up to: 6.5.4
Requires PHP: 7.4
WC requires at least: 5.6.0
WC tested up to: 9.0.0
Stable tag: 2.9.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html


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
6. Read more about the configuration process in the [plugin documentation](http://docs.krokedil.com/documentation/nets-easy-for-woocommerce/).


== Frequently Asked Questions ==
= Which countries does this payment gateway support? =
Available for merchants in Denmark, Sweden and Norway.

= Where can I find Nexi Checkout documentation? =
For help setting up and configuring Nexi Checkout please refer to our [documentation](http://docs.krokedil.com/documentation/nets-easy-for-woocommerce/).

= Are there any specific requirements? =
* WooCommerce 5.0 or newer is required.
* PHP 7.3 or higher is required.
* A SSL Certificate is required.
* This plugin integrates with Nexi Checkout. You need an agreement with Nets specific to the Nexi Checkout platform to use this plugin.

== CHANGELOG ==
= 2024.07.02    - version 2.9.1 =
* Fix           - Fixed missing payment gateway icons.
* Fix           - Escape redirect URLs for redirect and overlay checkout flow.
* Tweak         - Rebranding from Nets to Nexi.
* Tweak         - Updated links related to rebranding from Nets to Nexi.

= 2024.04.17    - version 2.9.0 =
* Tweak         - Tweaks related to PHPCS & WPCS before release on woo.com.
* Fix           - PHP8.1 compatibility fix. Add missing subscription class prop (thanks @khlieng).

= 2024.03.12    - version 2.8.2 =
* Fix           - Removed a unnecessary loop when registering blocks payment methods that generated a PHP notice.

= 2024.02.28    - version 2.8.1 =
* Fix           - Fix issue with ignored files when publishing to WordPress.org.

= 2024.02.28    - version 2.8.0 =
* Feature       - Adds support for WooCommerce blocks checkout using the redirect flow.
* Feature       - Adds support for switching between Scheduled and Unscheduled subscriptions in the payment method settings.
* Fix           - Fixes a potential fatal error when the WooCommerce order was not found when making a capture call.

= 2024.02.05    - version 2.7.1 =
* Tweak         - Scroll customer to shipping area in Woo when address-changed event has been triggered by Nets and user is on mobile.

= 2024.01.16    - version 2.7.0 =
* Feature       - The plugin now supports WooCommerce's "High-Performance Order Storage" ("HPOS") feature.
* Tweak         - Adds support for updating customer and session in Woo when applepay-contact-updated event is triggered.
* Tweak         - Adds class properties for PHP 8.2 compatibility (thanks @Stian).
* Tweak         - Change helper function nets_easy_get_order_id_by_purchase_id to nets_easy_get_order_by_purchase_id. Uses the same logic that Krokedil have in other payment plugins.
* Tweak         - Tweaks in logic how payment data is saved in subscription in set_recurring_token_for_order function.
* Fix           - Adds Nets Easy test environment hosted payment page as allowed external url for wp_safe_redirect.

= 2023.12.18    - version 2.6.2 =
* Fix           - Updates helper function to get refund order id due to changes in WP query. Fixes potential error triggered in refund request.

= 2023.11.13    - version 2.6.1 =
* Fix           - Fixed an issue related to pay for order when attempting to update the merchant reference.
* Fix           - Fixed an undefined index.

= 2023.10.10    - version 2.6.0 =
* Feature       - Adds card and Swish payment as standalone payment methods. Each payment method can be activated via the plugin general settings.
* Feature        - Adds filter nets_easy_embedded_order_reference so initial order reference in embedded checkout flow can be tweaked by merchant.
* Tweak         - Rename setting "Debug" to "Logging".
* Fix           - PHP8.1 deprecation notices fix.

= 2023.08.17    - version 2.5.3 =
* Tweak         - Set default request timeout time to 10 seconds and adds nets_easy_set_timeout filter to GET and PUT requests.
* Fix           - PHP8.1 deprecation notices fix (thanks @oxyc).

= 2023.07.25    - version 2.5.2 =
* Fix           - Solves issue with redirect to payment page when changing/updating subscription payment method. Add Nets Easy hosted payment page as allowed external url for wp_safe_redirect.

= 2023.07.17    - version 2.5.1 =
* Fix           - Blocked-based themes should now work as expected with the embedded checkout.

= 2023.06.15    - version 2.5.0 =
* Feature       - Adds support for Overlay Checkout flow. When selected, customers placing an order from desktop will see the the Nets payment window in an overlay instead of being redirected to the payment page.

= 2023.06.12    - version 2.4.1 =
* Enhancement   - Implemented an extra validation step during the checkout process to avoid any potential price discrepancies between WooCommerce and Nets.
* Tweak         - Fine-tuned the tax rate calculation in the redirect flow to ensure consistency with the embedded checkout flow.

= 2023.06.05    - version 2.4.0 =
* Tweak         - Send WooCommerce order number as order reference to Nets later in the process (when payment is confirmed in Woo) due to changes in Nets API.
* Fix           - Improvements in logic to allow multiple YITH giftcards in same order.

= 2023.04.26    - version 2.3.2 =
* Fix           - New improvement in logger logic, to avoid potential PHP issue with some plugins.

= 2023.04.20    - version 2.3.1 =
* Tweak         - Remove maybeChangeToDibsEasy logic from nets-easy-for-woocommerce.js. This has been moved to nets-easy-utility.js.
* Fix           - Avoid division by zero issue if fee is 0.
* Fix           - Improvements in logger logic. Check if log object is instance of WP_Hook to avoid potential fatal error.

= 2023.04.13    - version 2.3.0 =
* Feature       - Adds settings for custom payment gateway icon.
* Feature       - Add setting to control if Nets payment data (payment method, payment ID and masked card number) should be added to customer email.
* Feature       - Adds support for gift cards via Smart coupons plugin.
* Feature       - Adds filter nets_easy_ignored_checkout_fields to allow others to alter which fields appear under additional fields (thanks @oxyc).
* Tweak         - Reload checkout page and try to send update request again to Nets if response code is 409 on an update.
* Tweak         - Change logic for auto capture. We now send charge = true in the create request and look for a charge id in payment confirmation, instead of making an activation request in payment confirmation sequenze.
* Tweak         - Improved logic for enqueuing of js files to avoid creation of Nets payment ID when checkout page is rendered but Nets Easy isn't the selected payment gateway.
* Tweak         - Change sku sent for YITH giftcard. Now we use giftcard code to allow multiple cift card used n one order.
* Tweak         - Tweak to YITH giftcard name sent to Nets, use the format Gift card: {gifdcard_code}.
* Tweak         - Adds payment ID to log for charge order, cancel order and update cart requests.
* Tweak         - Tweak in refund message logged as order note. Reason not needed.
* Fix           - PHP 8.1 deprecated notice fix.
* Fix           - Reload checkout page if cart doesn't need payment for embedded checkoutflow. So that regular Woo checkout template is used instead.

= 2023.02.28    - version 2.2.2 =
* Tweak         - Do not send webhook url to Nets if the site is declared as a local environment via wp_get_environment_type(). Previously this was checked via $_SERVER['REMOTE_ADDR'] & $_SERVER['HTTP_HOST'].
* Fix           - Improved multi currency support. Creates new Nets payment ID if currency is changed mid session.

= 2022.12.14    - version 2.2.1 =
* Tweak         - Improvement in logic regarding product & shipping method name cleaning. We now remove specific characters not supported by Nets.
* Fix           - Avoid fatal error in confirmation sequence if request to Nets fails.
* Fix           - Avoid fatal error in process payment sequence (redirect checkout flow) if request to Nets fails.

= 2022.11.09    - version 2.2.0 =
* Feature       - Adds customer name and address in create session request to Nets (if they exist in Woo) for embedded checkout.
* Tweak         - Sends cancel url in payment requests to Nets.
* Fix           - Better handling of finalizing purchase in WooCommerce if customer is redirected back to store in another browser than what the purchase started with.
* Fix           - Use mb_ereg_replace instead of preg_replace to avoid certain characters not approved by Nets in product and shipping names.
* Fix           - Save recurring token correctly for unscheduled subscriptions when changing/updating payment method from My account page.
* Fix           - Adds a unique script name when enqueuing checkout script. To avoid conflicts with other payment plugins.

= 2022.09.07    - version 2.1.0 =
* Feature       - Adds support for Nets Easy unscheduled subscriptions. Can only be activated via filter nets_easy_subscription_type for now.
* Fix           - Fix bug not possible to update recurring token from subscriptions page.
* Fix           - Use get_date_paid in thank you page (instead of order status) to determine if confirm order process should be triggered.

= 2022.07.27    - version 2.0.7 =
* Fix           - Fixed an issue where if a scheduled subscription failed to renew would result in a fatal error.

= 2022.07.22    - version 2.0.6 =
* Fix           - Fix SVN and stable tag versioning.

= 2022.07.14    - version 2.0.4 =
* Fix           - Fixed a critical error happening when attempting to process and print an error message.
* Fix           - Fixed checkout template filter not working with Redis Cache (thanks @oxyc!)
* Tweak         - Plugin assets won't be loaded on the thank-you page anymore.
* Tweak         - We no longer create a new session unless the selected payment method is Nets Easy.

= 2022.06.30    - version 2.0.3 =
* Fix           - Fix issues with error handling from the Nets API with order management. Solves the errors that would show as "Uncaught Error" in the WooCommerce logs.

= 2022.06.09    - version 2.0.2 =
* Fix           - Fix various fatal errors.
* Fix           - Fix payment id not being logged.
* Fix           - The text for the "payment complete" button should now show the text specified in the settings for subscription payments.

= 2022.05.27    - version 2.0.1 =
* Fix           - Fixed an issue where the test checkout key would be used in the frontend even when testmode was disabled.

= 2022.05.25    - version 2.0.0 =
* Enhancement   - Internal rewrite of plugin to better follow WP & Krokedil standards.
* Enhancement   - Improved the speed of update calls to Nets to enhance the checkout experience for the customer.
* Enhancement   - Adds minification of js & css files.
* Fix           - Create new session if subscription product is added to or removed from cart after initial request. This way the place order button text should always be correct.

= 2022.04.11    - version 1.26.0 =
* Tweak         - The argnum in format specifier for translatable strings is removed to offer better compatibility with third-party translation API.
* Fix           - (Redirect) Fix the error message on checkout when Nets denies the create payment request.
* Fix           - Fix critical error sometimes happening when saving the shipping reference in the order.

= 2022.01.28    - version 1.25.1 =
* Fix           - Remove white space in all phone numbers sent to Nets.
* Fix           - Fix display of error message in checkout if Nets denies create payment request for redirect checkout flow.

= 2022.01.14    - version 1.25.0 =
* Enhancement   - Add support for refunds even for A2A payment types (like Swish).

= 2021.11.18    - version 1.24.1 =
* Tweak         - Strip postal code/ZIP of potential spaces. Nets Easy does not allow this.

= 2021.11.11    - version 1.24.0 =
* Feature       - Adds setting to set checkout description.
* Tweak         - Do not set webhooks if Host is localhost. Makes it easier to test Nets Easy in a local development environment.
* Tweak         - Bump required PHP version to 7.0.
* Tweak         - Replacing all mentions of "Easy" on its own to be "Nets Easy".
* Tweak         - Replaces old Docs links to current URLs.
* Fix           - Division by zero fix that could happen in taxRate calculation.
* Fix           - Division by zero fix that could happen in refund request.
* Fix           - PHP 8 related issue fix that could prevent redirect to Nets payment window.
* Fix           - PHP 8 related issue fix that could cause partial refund to fail due to expected array missing.


= 2021.06.29    - version 1.23.4 =
* Fix           - Improvement in woocommerce_order_needs_payment filter. Could cause Pay for order button being visible on My account pages even for paid orders.

= 2021.06.28    - version 1.23.3 =
* Fix           - Change array_key_exists to isset to better handle PHP 8 compatibility.
* Fix           - Create new payment session if currency changes during an ongoing session in checkout.

= 2021.05.25    - version 1.23.2 =
* Tweak         - Use Action scheduler instead of WP cron for queuing payment created webhook handling. This is a more reliable solution.
* Tweak         - Only try to send the customer data to Nets that actually exist in WooCommerce order. Improves redirect flow where the store doesn't enable full address collecting.

= 2021.05.10    - version 1.23.1 =
* Tweak         - Only send information about completePaymentButtonText if cart contain subscription. Solves issue where "Subscribe" text could be displayed in pay button even for regular purchases.

= 2021.05.03    - version 1.23.0 =
* Feature       - Updated countries supported by Nets (https://tech.dibspayment.com/easy/api/datastring-parameters).
* Tweak         - Change DIBS to Nets in readme and settings.
* Tweak         - Remove old/unused code related to backup order creation.
* Tweak         - Remove code for saving order note field & extra checkout field data in session storage. Not needed in current embedded checkout flow.
* Fix           - Improved logic for handling payment processing on API callback. Fixes issues with Swish/Vipps payments where customer never returns to store/browser after completed payment.
* Fix           - Improve function get_order_id_from_payment_id. Don't try to make a query to db if missing payment_id.

= 2021.03.09    - version 1.22.0 =
* Tweak         - Remove backup order creation feature. WooCommerce order should always be created on pay-initialized JS event.
* Fix           - Modify filter woocommerce_order_needs_payment so recurring token is saved in WooCommerce subscription even if initial order contain a recurring coupon that results in a 0 value order.

= 2021.03.05    - version 1.21.2 =
* Tweak         - Updated url to WooCommerce.com docs about configuring terms page.
* Fix           - Charge id check resulted in refunds not working properly.
* Fix           - PHP notice fix in admin notices class.

= 2021.02.24    - version 1.21.1 =
* Tweak         - Improved logging. Move logging logic to separate class.
* Tweak         - Log file now named nets_easy in WooCommerce admin.
* Tweak         - Display the returned error message in order note if error code 1001 is returned in charge request.
* Fix           - Don't trigger a charge/activation request to Nets if WC order total is 0.
* Fix           - Add order note if missing charge id in refund request.
* Fix           - Don't try to instantiate Post_Checkout class twice.

= 2021.02.05    - version 1.21.0 =
* Feature       - Add Merchant Number setting. Only required if you are a partner and initiating the checkout with your partner keys.
* Tweak         - Only send phone number to Nets if it exist in the WC order (for redirect flow).
* Fix           - Change subscription time to 5 years. Caused issue with some card processors.

= 2020.12.29    - version 1.20.5 =
* Fix           - Fix email line break bug. Introduced in previous coding standard update.

= 2020.12.18    - version 1.20.4 =
* Tweak         - Add setting for Select another payment method button text.

= 2020.12.15    - version 1.20.3 =
* Tweak         - Coding standard improvements.

= 2020.11.30    - version 1.20.2 =
* Tweak         - Interpret and send WP locale de_CH, de_AT & de_DE_formal as German (de-DE) to Nets.

= 2020.11.23    - version 1.20.1 =
* Tweak         - Remove unused code.
* Fix           - Add auto capture trigger (if selected in settings) in payment created callback.
* Fix           - Fix potential float issue in subscription renewal order request.
* Fix           - Don't overwrite the charge id in Woo order post meta if the payment already have been charged in Nets Easy system.

= 2020.11.18    - version 1.20.0 =
* Feature       - Add compatibility support with YITH Gift Card plugin.
* Tweak         - Don't send company name to Nets (in redirect flow) if B2C is the only allowed customer type.
* Fix           - Rounding issue fix in cart netTotalAmount sent to Nets.
* Fix           - Only try to add invoice fee data  to Nets if we actually have an invoice fee product.

= 2020.09.30    - version 1.19.1 =
* Fix           - Security update. Block order review area during 3DSecure/Swish/MobilePay/Vipps processing sequence.

= 2020.09.21    - version 1.19.0 =
* Feature       - Added support for auto-capture (automatically charge payment in nets directly after puchase is completed by customer).
* Feature       - Added support for order pay link even if embedded checkout flow is selected.
* Tweak         - Change from Bulk Charge to Single Charge endpoint request for renewal subscription orders.
* Tweak         - Change wording in WC order notes for created payment, charge requests and refund requests.
* Fix           - Use isset instead of array_key_exists to avoid php deprecated notice issue.

= 2020.06.29    - version 1.18.0 =
* Feature		- Added support for German, Polish, French, Dutch, Finnish and Spain locale.

= 2020.06.24    - version 1.17.0 =
* Tweak         - Create confirmation step before redirecting customer to thankyou page. Displays invoice fee correctly in thank you page.
* Fix           - Make externalBulkChargeId an unique value to be able to try multiple renewal requests for one order.
* Fix           - Rounding fix in cart shipping calculation.
* Fix           - Rounding fix in amount in get order class.

= 2020.05.22    - version 1.16.1 =
* Tweak         - Add invoice fee to WooCommerce order before payment_complete runs.
* Fix           - Include class-get-subscription-by-external-refernce.php correctly.

= 2020.05.15    - version 1.16.0 =
* Tweak         - Removed currency control to determine if Nets Easy payment gatway should be available or not. Note - if needed this logic must be handled by the merchant/store from now on.

= 2020.04.30    - version 1.15.2 =
* Tweak         - Only set merchantHandlesShippingCost to true if WooCommerce needs an address before calculate shipping (woocommerce_shipping_cost_requires_address = yes).
* Tweak         - Set postalCode as null in request sent to Nets if we do not have a wc billing postcode.
* Tweak         - Change product->get_name() to order_item->get_name() in requests sent to Nets.
* Fix           - Improved phone number prefix handling. Now supports all countries that WooCommerce supports.
* Fix           - Only try to get a sku from the product (in requests to Nets) if we have an instance of the product object.

= 2020.04.01    - version 1.15.1 =
* Tweak         - Only register webhook if host is not local (127.0.0.1 or ::1).
* Fix           - Don't send -> shipping -> countries in request to Nets if redirect checkout flow is used.

= 2020.03.02    - version 1.15.0 =
* Feature       - Add support for EUR.

= 2020.03.02    - version 1.14.1 =
* Fix           - Fixed an issue that caused the recurring token to be removed in some cases for subscription orders.
* Enhancement   - Better support for table rate shipping, and other plugins that changes the shipping reference.
* Enhancement   - Added a filter to the update order request. *dibs_easy_update_order_args*.
* Enhancement   - Added a filter to be able to change timeouts for requests. *nets_easy_set_timeout*
* Enhancement   - Added possibility to add an invoice fee to the redirect flow.


= 2020.02.05    - version 1.14.0 =
* Fix           - Modified redirect url set in process_payment function to improve checkout flow for purchases canceled/denied in 3DSecure window.
* Fix           - Triggering update_checkout if GET params paymentId and PaymentFailed is set. Caused errors with subscription based payments where the nonce had to be updated.

= 2020.01.22    - version 1.13.1 =
* Tweak         - Added support for changing payment method on a subscription for customers.
* Fix           - Format phone number sent to Nets correct on redirect checkout flow.
* Fix           - Tweak in logic for the GTM fix added in v1.13.0.

= 2019.12.12    - version 1.13.0 =
* Feature       - Added support for partial refunds.
* Feature       - Added setting for selecting the "Complete payment" button text on subscription based payments.
* Tweak         - Changed plugin name to Nets Easy for WooCommerce.
* Fix           - Only update WC customer address data in JS event from DIBS if postal code or country have value or is changed.
* Fix           - Shipping reference not being set correct when activating order.
* Fix           - Only run function for changing to Easy payment method if no hashChange has been made (checkout process has begun). Caused an issue with Google Tag Manager for WordPress by Thomas Geiger.


= 2019.11.12    - version 1.12.0 =
* Feature       - Add support for getting DIBS subscription ID from externalreference (support for D2 to Easy subscription transfer).
* Tweak         - Updated subscription renewal payment logic to work with newer versions of WooCommerce Subscriptions.
* Fix           - Rounding fix in check order totals function.
* Fix           - Save DIBS payment method to order when it is finalized in the fallback sequence (can happen when customer not navigates back to store after Swish/Vipps purchase).

= 2019.10.08    - version 1.11.1 =
* Fix           - Remove including of file that doesn't exist in plugin. Caused error.

= 2019.10.08    - version 1.11.0 =
* Feature       - Add language support for redirect checkout flow.
* Feature       - Added support for B2B purchases with redirect flow.
* Tweak         - Send payment_method instead of payment_type in order confirmation emails.
* Fix           - Make sure WooCommerce Subscriptions plugin exist before trying to save DIBS subscription id to subscriptions.
* Fix           - Rounding fix in order item prices sent to DIBS with embedded checkout flow.
* Fix           - Rounding fix in order total sent to DIBS with redirect checkout flow.
* Fix           - Send correct item taxRate to DIBS for redirect checkout flow and order management requests.

= 2019.09.18    - version 1.10.5 =
* Fix           - Rounding fix in order item prices sent to DIBS with redirect checkout flow.
* Fix           - Rounding fix in order total sent to DIBS in order management requests.

= 2019.08.22    - version 1.10.4 =
* Fix           - Remove including of file class-dibs-create-local-order-callback.php (file was removed in 1.10.3).

= 2019.08.22    - version 1.10.3 =
* Tweak         - Remove checkout_error order creation code. Not used anymore (since of version 1.10.0).
* Tweak         - Don't send web hooks to DIBS if host is local.
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
