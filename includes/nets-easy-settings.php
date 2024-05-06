<?php
/**
 * Nexi settings class.
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Settings for Nexi Checkout
 */
return apply_filters(
	'dibs_easy_settings',
	array(
		'enabled'                      => array(
			'title'   => __( 'Enable/Disable', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Nexi Checkout', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'title'                        => array(
			'title'       => __( 'Title', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'This is the title that the user sees on the checkout page for Nexi Checkout.', 'dibs-easy-for-woocommerce' ),
			'default'     => __( 'Nexi Checkout', 'dibs-easy-for-woocommerce' ),
		),
		'description'                  => array(
			'title'       => __( 'Description', 'dibs-easy-for-woocommerce' ),
			'type'        => 'textarea',
			'default'     => '',
			'desc_tip'    => true,
			'description' => __( 'This controls the description which the user sees during checkout.', 'dibs-easy-for-woocommerce' ),
		),
		'merchant_number'              => array(
			'title'       => __( 'Merchant ID', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'The stores Nexi Checkout Merchant ID. Only required if you are a partner and initiating the checkout with your partner keys.', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
		),
		'dibs_live_key'                => array(
			'title'       => __( 'Live Secret key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your Nexi live secret key', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'dibs_checkout_key'            => array(
			'title'       => __( 'Live Checkout key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your Nexi live checkout key', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'dibs_test_key'                => array(
			'title'       => __( 'Test Secret key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your Nexi Test secret key if you want to run in test mode.', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'dibs_test_checkout_key'       => array(
			'title'       => __( 'Test Checkout key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your Nexi Test checkout key', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'test_mode'                    => array(
			'title'   => __( 'Test mode', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Test mode for Nexi Checkout', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'allowed_customer_types'       => array(
			'title'       => __( 'Allowed Customer Types', 'dibs-easy-for-woocommerce' ),
			'type'        => 'select',
			'options'     => array(
				'B2C'  => __( 'B2C only', 'dibs-easy-for-woocommerce' ),
				'B2B'  => __( 'B2B only', 'dibs-easy-for-woocommerce' ),
				'B2CB' => __( 'B2C & B2B (defaults to B2C)', 'dibs-easy-for-woocommerce' ),
				'B2BC' => __( 'B2B & B2C (defaults to B2B)', 'dibs-easy-for-woocommerce' ),
			),
			'description' => __( 'Select if you want to sell both to consumers and companies or only to one of them.', 'dibs-easy-for-woocommerce' ),
			'default'     => 'B2C',
			'desc_tip'    => false,
		),
		'email_text'                   => array(
			'title'       => __( 'Email text', 'dibs-easy-for-woocommerce' ),
			'type'        => 'textarea',
			'description' => __( 'This text will be added to your customers order confirmation email.', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
		),
		'email_nets_payment_data'      => array(
			'title'   => __( 'Email payment data', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Add Nexi payment data to order confirmation email.', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'dibs_manage_orders'           => array(
			'title'   => __( 'Manage orders', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable WooCommerce to manage orders in Nexi Checkout backend', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'debug_mode'                   => array(
			'title'       => __( 'Logging', 'dibs-easy-for-woocommerce' ),
			'type'        => 'checkbox',
			'label'       => __( 'Log debug messages', 'dibs-easy-for-woocommerce' ),
			'description' => __( 'Save debug messages to the WooCommerce System Status log', 'dibs-easy-for-woocommerce' ),
			'default'     => 'yes',
			'desc_tip'    => true,
		),
		'dibs_invoice_fee'             => array(
			'title'       => __( 'Invoice fee ID', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => sprintf( __( 'Create a hidden (simple) product that acts as the invoice fee. Enter the product <strong>ID</strong> number in this textfield. Leave blank to disable.', 'dibs-easy-for-woocommerce' ) ),
			'default'     => '',
			'desc_tip'    => false,
		),
		'checkout_flow'                => array(
			'title'       => __( 'Checkout flow', 'dibs-easy-for-woocommerce' ),
			'type'        => 'select',
			'options'     => array(
				'embedded' => __( 'Embedded', 'dibs-easy-for-woocommerce' ),
				'redirect' => __( 'Redirect', 'dibs-easy-for-woocommerce' ),
				'overlay'  => __( 'Overlay', 'dibs-easy-for-woocommerce' ),
			),
			'description' => __( 'Select how Nexi Checkout should be integrated in WooCommerce. <strong>Embedded</strong> – the checkout is embedded in the WooCommerce checkout page and partially replaces the checkout form. <strong>Redirect</strong> – the customer is redirected to a payment page hosted by Nexi. <strong>Overlay</strong> – similar logic as redirect flow but the hosted payment window is displayed in an overlay on desktop.', 'dibs-easy-for-woocommerce' ),
			'default'     => 'embedded',
			'desc_tip'    => false,
		),
		'auto_capture'                 => array(
			'title'   => __( 'Auto-capture', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Auto-capture. If enabled Nexi Checkout charges your customer immediately after payment completion. Only enable for compliant products/services.', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'select_another_method_text'   => array(
			'title'       => __( 'Other payment method button text', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Customize the <em>Select another payment method</em> button text that is displayed in checkout if using other payment methods than Nexi Checkout. Leave blank to use the default (and translatable) text.', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'complete_payment_button_text' => array(
			'title'       => __( 'Subscription payment button text', 'dibs-easy-for-woocommerce' ),
			'type'        => 'select',
			'options'     => array(
				'pay'       => 'Pay',
				'purchase'  => 'Purchase',
				'order'     => 'Order',
				'book'      => 'Book',
				'reserve'   => 'Reserve',
				'signup'    => 'Signup',
				'subscribe' => 'Subscribe',
				'accept'    => 'Accept',

			),
			'description' => __( 'Select the text displayed on the complete payment button. Only applicable for subscription based payments. Translations for the selections can be found <a href="https://docs.krokedil.com/nets-easy-for-woocommerce/get-started/subscription-support/#custom-button-text-for-subscription-payments" target="_blank">here.</a>', 'dibs-easy-for-woocommerce' ),
			'default'     => 'subscribe',
			'desc_tip'    => false,
		),
		'subscription_type'            => array(
			'title'       => __( 'Subscription type', 'dibs-easy-for-woocommerce' ),
			'type'        => 'select',
			'options'     => array(
				'scheduled_subscription'   => __( 'Scheduled subscriptions', 'dibs-easy-for-woocommerce' ),
				'unscheduled_subscription' => __( 'Unscheduled subscriptions', 'dibs-easy-for-woocommerce' ),
			),
			// Translators: URL to Nexi with info about subscriptions.
			'description' => sprintf( __( 'If using Nexi Checkout together with Woo Subscriptions, select the subscription type to use. Read more about scheduled vs unscheduled subscriptions <a href="%s" target="_blank">here</a>.', 'dibs-easy-for-woocommerce' ), 'https://ecom.nets.eu/subscriptions/' ),
			'default'     => 'scheduled_subscription',
			'desc_tip'    => false,
		),
		'payment_gateway_icon'         => array(
			'title'       => __( 'Payment gateway icon', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter an URL to the icon you want to display for the payment method. Use <i>default</i> to display the default Nexi logo. Leave blank to not show an icon at all.', 'dibs-easy-for-woocommerce' ),
			'default'     => 'default',
			'desc_tip'    => false,
		),
		'payment_gateway_icon_width'   => array(
			'title'       => __( 'Payment gateway icon width', 'dibs-easy-for-woocommerce' ),
			'type'        => 'number',
			'description' => __( 'Specify the max width (in px) of the payment gateway icon.', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		// SE.
		'payment_method_split_title'   => array(
			'title'       => __( 'Standalone payment methods', 'dibs-easy-for-woocommerce' ),
			'description' => __( 'Enable specific payment methods as standalone payment methods in the checkout.', 'dibs-easy-for-woocommerce' ),
			'type'        => 'title',
		),
		'enable_payment_method_card'   => array(
			'title'   => __( 'Card payment', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Card payment as separate payment method', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		/*
		'enable_payment_method_sofort'       => array(
			'title'   => __( 'Sofort payment', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Sofort payment as separate payment method', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'enable_payment_method_trustly'      => array(
			'title'   => __( 'Trustly payment', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Trustly payment as separate payment method', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		*/
		'enable_payment_method_swish'  => array(
			'title'   => __( 'Swish payment', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Swish payment as separate payment method', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		/*
		'enable_payment_method_ratepay_sepa' => array(
			'title'   => __( 'Ratepay SEPA payment', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Ratepay SEPA payment as separate payment method', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		*/
	)
);
