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
	'dibs_easy_trustly_settings',
	array(
		'enabled'                    => array(
			'title'   => __( 'Enable/Disable', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Nexi Checkout Trustly', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'title'                      => array(
			'title'       => __( 'Title', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'This is the title that the user sees on the checkout page for Nexi Checkout.', 'dibs-easy-for-woocommerce' ),
			'default'     => __( 'Trustly payment', 'dibs-easy-for-woocommerce' ),
		),
		'description'                => array(
			'title'       => __( 'Description', 'dibs-easy-for-woocommerce' ),
			'type'        => 'textarea',
			'default'     => '',
			'desc_tip'    => true,
			'description' => __( 'This controls the description which the user sees during checkout.', 'dibs-easy-for-woocommerce' ),
		),
		'payment_gateway_icon'       => array(
			'title'       => __( 'Payment gateway icon', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'Enter an URL to the icon you want to display for the payment method. Use <i>default</i> to display the default Nexi logo. Leave blank to not show an icon at all.', 'dibs-easy-for-woocommerce' ),
			'default'     => 'default',
			'desc_tip'    => false,
		),
		'payment_gateway_icon_width' => array(
			'title'       => __( 'Payment gateway icon width', 'dibs-easy-for-woocommerce' ),
			'type'        => 'number',
			'description' => __( 'Specify the max width (in px) of the payment gateway icon.', 'dibs-easy-for-woocommerce' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'available_countries'        => array(
			'title'       => __( 'Available countries', 'dibs-easy-for-woocommerce' ),
			'type'        => 'multiselect',
			'class'       => 'wc-enhanced-select',
			'description' => __( 'Select which countries the payment method should be available for.', 'dibs-easy-for-woocommerce' ),
			'options'     => array(
				'AT' => __( 'Austria', 'dibs-easy-for-woocommerce' ),
				'CZ' => __( 'Czech Republic', 'dibs-easy-for-woocommerce' ),
				'DK' => __( 'Denmark', 'dibs-easy-for-woocommerce' ),
				'EE' => __( 'Estonia', 'dibs-easy-for-woocommerce' ),
				'FI' => __( 'Finland', 'dibs-easy-for-woocommerce' ),
				'DE' => __( 'Germany', 'dibs-easy-for-woocommerce' ),
				'LV' => __( 'Latvia', 'dibs-easy-for-woocommerce' ),
				'LT' => __( 'Lithuania', 'dibs-easy-for-woocommerce' ),
				'NO' => __( 'Norway', 'dibs-easy-for-woocommerce' ),
				'PL' => __( 'Poland', 'dibs-easy-for-woocommerce' ),
				'SK' => __( 'Slovakia', 'dibs-easy-for-woocommerce' ),
				'ES' => __( 'Spain', 'dibs-easy-for-woocommerce' ),
				'SE' => __( 'Sweden', 'dibs-easy-for-woocommerce' ),
				'NL' => __( 'The Netherlands', 'dibs-easy-for-woocommerce' ),
				'GB' => __( 'United Kingdom', 'dibs-easy-for-woocommerce' ),
			),
			'default'     => '',
			'desc_tip'    => true,
		),
	)
);
