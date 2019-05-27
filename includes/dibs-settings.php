<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Settings for DIBS Easy
 */

return apply_filters(
	'dibs_easy_settings',
	array(
		'enabled'                => array(
			'title'   => __( 'Enable/Disable', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable DIBS Easy', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'title'                  => array(
			'title'       => __( 'Title', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'This is the title that the user sees on the checkout page for DIBS Easy.', 'dibs-easy-for-woocommerce' ),
			'default'     => __( 'DIBS Easy', 'dibs-easy-for-woocommerce' ),
		),
		'dibs_live_key'          => array(
			'title'       => __( 'Live Secret key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your DIBS Easy live key', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'dibs_checkout_key'      => array(
			'title'       => __( 'Live Checkout key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your DIBS Easy Checkout key', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'dibs_test_key'          => array(
			'title'       => __( 'Test Secret key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your DIBS Easy Test key if you want to run in test mode.', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'dibs_test_checkout_key' => array(
			'title'       => __( 'Test Checkout key', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter your DIBS Easy Test checkout key', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'test_mode'              => array(
			'title'   => __( 'Test mode', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Test mode for DIBS Easy', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'allowed_customer_types' => array(
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
		'email_text'             => array(
			'title'       => __( 'Email text', 'dibs-easy-for-woocommerce' ),
			'type'        => 'textarea',
			'description' => __( 'This text will be added to your customers order confirmation email.', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
		),
		'dibs_manage_orders'     => array(
			'title'   => __( 'Manage orders', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable WooCommerce to manage orders in DIBS Easys backend', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'debug_mode'             => array(
			'title'   => __( 'Debug mode', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Debug mode for DIBS Easy', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'dibs_invoice_fee'       => array(
			'title'       => __( 'Invoice fee ID', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => sprintf( __( 'Create a hidden (simple) product that acts as the invoice fee. Enter the product <strong>ID</strong> number in this textfield. Leave blank to disable.', 'dibs-easy-for-woocommerce' ) ),
			'default'     => '',
			'desc_tip'    => false,
		),
		'checkout_flow'          => array(
			'title'       => __( 'Checkout flow', 'dibs-easy-for-woocommerce' ),
			'type'        => 'select',
			'options'     => array(
				'embedded' => __( 'Embedded', 'dibs-easy-for-woocommerce' ),
				'redirect' => __( 'Redirect', 'dibs-easy-for-woocommerce' ),
			),
			'description' => __( 'Select how Easy should be integrated in WooCommerce.', 'dibs-easy-for-woocommerce' ),
			'default'     => 'embedded',
			'desc_tip'    => false,
		),
	)
);
