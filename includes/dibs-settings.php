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
			'title'   => __( 'Enable/Disable', 'woocommerce-dibs-easy' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable DIBS Easy', 'woocommerce-dibs-easy' ),
			'default' => 'no',
		),
		'title'   => array(
			'title'         => __( 'Title', 'woocommerce-dibs-easy' ),
			'type'          => 'text',
			'description'   => __( 'This is the title that the user sees on the checkout page for DIBS Easy.', 'woocommerce-dibs-easy' ),
			'default'       => __( 'DIBS Easy', 'woocommerce-dibs-easy' ),
		),
		'dibs_live_key'     => array(
			'title'         => __( 'Live Key', 'woocommerce-dibs-easy' ),
			'type'          => 'text',
			'description'   => __( 'Enter your DIBS Easy live key', 'woocommerce-dibs-easy' ),
			'default'       => '',
			'desc_tip'      => true,
		),
		'dibs_test_key'     => array(
			'title'         => __( 'Test Key', 'woocommerce-dibs-easy' ),
			'type'          => 'text',
			'description'   => __( 'Enter your DIBS Easy Test key if you want to run in test mode.', 'woocommerce-dibs-easy' ),
			'default'       => '',
			'desc_tip'      => true,
		),
		'dibs_private_key'  => array(
			'title'         => __( 'Private key', 'woocommerce-dibs-easy' ),
			'type'          => 'text',
			'description'   => __( 'Enter your DIBS Easy Private key', 'woocommerce-dibs-easy' ),
			'default'       => '',
			'desc_tip'      => true,
		),
		'test_mode'         => array(
			'title'         => __( 'Test mode', 'woocommerce-dibs-easy' ),
			'type'          => 'checkbox',
			'label'         => __( 'Enable Test mode for DIBS Easy', 'woocommerce-dibs-easy' ),
			'default'       => 'no',
		),
	)
);
