<?php
/**
 * Nets settings class.
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Settings for Nets Easy
 */
return apply_filters(
	'dibs_easy_settings',
	array(
		'enabled'                      => array(
			'title'   => __( 'Enable/Disable', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Nets Easy', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'title'                        => array(
			'title'       => __( 'Title', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'This is the title that the user sees on the checkout page for Nets Easy.', 'dibs-easy-for-woocommerce' ),
			'default'     => __( 'Nets Easy', 'dibs-easy-for-woocommerce' ),
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
			'description' => __( 'The stores Nets Easy Merchant ID. Only required if you are a partner and initiating the checkout with your partner keys.', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
		),
		'dibs_live_key'                => array(
			'title'       => __( 'Live Secret key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your Nets Easy live key', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'dibs_checkout_key'            => array(
			'title'       => __( 'Live Checkout key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your Nets Easy Checkout key', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'dibs_test_key'                => array(
			'title'       => __( 'Test Secret key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your Nets Easy Test key if you want to run in test mode.', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'dibs_test_checkout_key'       => array(
			'title'       => __( 'Test Checkout key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your Nets Easy Test checkout key', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'test_mode'                    => array(
			'title'   => __( 'Test mode', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Test mode for Nets Easy', 'dibs-easy-for-woocommerce' ),
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
		'dibs_manage_orders'           => array(
			'title'   => __( 'Manage orders', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable WooCommerce to manage orders in Nets Easy backend', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'debug_mode'                   => array(
			'title'   => __( 'Debug mode', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Debug mode for Nets Easy', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
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
			),
			'description' => __( 'Select how Nets Easy should be integrated in WooCommerce. <strong>Embedded</strong> – the checkout is embedded in the WooCommerce checkout page and partially replaces the checkout form. <strong>Redirect</strong> – the customer is redirected to a payment page hosted by Nets.', 'dibs-easy-for-woocommerce' ),
			'default'     => 'embedded',
			'desc_tip'    => false,
		),
		'auto_capture'                 => array(
			'title'   => __( 'Auto-capture', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Auto-capture. If enabled Nets Easy charges your customer immediately after payment completion. Only enable for compliant products/services.', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'select_another_method_text'   => array(
			'title'       => __( 'Other payment method button text', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Customize the <em>Select another payment method</em> button text that is displayed in checkout if using other payment methods than Nets Easy. Leave blank to use the default (and translatable) text.', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'complete_payment_button_text' => array(
			'title'       => __( 'Complete payment button text', 'dibs-easy-for-woocommerce' ),
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
			'description' => __( 'Select the text displayed on the complete payment button. (Only applicable for subscription based payments). Translations for the selections can be found <a href="https://docs.krokedil.com/nets-easy-for-woocommerce/get-started/subscription-support/#custom-button-text-for-subscription-payments" target="_blank">here.</a>', 'dibs-easy-for-woocommerce' ),
			'default'     => 'subscribe',
			'desc_tip'    => false,
		),
	)
);
