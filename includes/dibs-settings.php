<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Settings for DIBS Easy
 */

return apply_filters( 'dibs_easy_settings',
	array(
		'enabled' => array(
			'title'   => __( 'Enable/Disable', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable DIBS Easy', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'title'   => array(
			'title'         => __( 'Title', 'dibs-easy-for-woocommerce' ),
			'type'          => 'text',
			'description'   => __( 'This is the title that the user sees on the checkout page for DIBS Easy.', 'dibs-easy-for-woocommerce' ),
			'default'       => __( 'DIBS Easy', 'dibs-easy-for-woocommerce' ),
		),
		'dibs_live_key'     => array(
			'title'         => __( 'Live Key', 'dibs-easy-for-woocommerce' ),
			'type'          => 'text',
			'description'   => __( 'Enter your DIBS Easy live key', 'dibs-easy-for-woocommerce' ),
			'default'       => '',
			'desc_tip'      => true,
		),
		'dibs_checkout_key'  => array(
			'title'         => __( 'Checkout key', 'dibs-easy-for-woocommerce' ),
			'type'          => 'text',
			'description'   => __( 'Enter your DIBS Easy Checkout key', 'dibs-easy-for-woocommerce' ),
			'default'       => '',
			'desc_tip'      => true,
		),
		'dibs_test_key'     => array(
			'title'         => __( 'Test Key', 'dibs-easy-for-woocommerce' ),
			'type'          => 'text',
			'description'   => __( 'Enter your DIBS Easy Test key if you want to run in test mode.', 'dibs-easy-for-woocommerce' ),
			'default'       => '',
			'desc_tip'      => true,
		),
		'dibs_test_checkout_key'  => array(
			'title'         => __( 'Test checkout key', 'dibs-easy-for-woocommerce' ),
			'type'          => 'text',
			'description'   => __( 'Enter your DIBS Easy Test checkout key', 'dibs-easy-for-woocommerce' ),
			'default'       => '',
			'desc_tip'      => true,
		),
		'test_mode'         => array(
			'title'         => __( 'Test mode', 'dibs-easy-for-woocommerce' ),
			'type'          => 'checkbox',
			'label'         => __( 'Enable Test mode for DIBS Easy', 'dibs-easy-for-woocommerce' ),
			'default'       => 'no',
		),
		'email_text'        => array(
			'title'         => __( 'Email text', 'dibs-easy-for-woocommerce' ),
			'type'          => 'textarea',
			'description'   => __( 'This text will be added to your customers order confirmation email.', 'dibs-easy-for-woocommerce' ),
			'default'       => '',
		),
		'dibs_manage_orders' => array(
			'title'         => __( 'Manage orders', 'dibs-easy-for-woocommerce' ),
			'type'          => 'checkbox',
			'label'         => __( 'Enable WooCommerce to manage orders in DIBS Easys backend', 'dibs-easy-for-woocommerce' ),
			'default'       => 'no',
		),
		'debug_mode'         => array(
			'title'         => __( 'Debug mode', 'dibs-easy-for-woocommerce' ),
			'type'          => 'checkbox',
			'label'         => __( 'Enable Debug mode for DIBS Easy', 'dibs-easy-for-woocommerce' ),
			'default'       => 'no',
		),
	)
);
