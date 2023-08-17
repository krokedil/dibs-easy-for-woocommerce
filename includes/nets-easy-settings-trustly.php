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
	'dibs_easy_trustly_settings',
	array(
		'enabled'                    => array(
			'title'   => __( 'Enable/Disable', 'dibs-easy-for-woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Enable Nets Easy Trustly', 'dibs-easy-for-woocommerce' ),
			'default' => 'no',
		),
		'title'                      => array(
			'title'       => __( 'Title', 'dibs-easy-for-woocommerce' ),
			'type'        => 'text',
			'description' => __( 'This is the title that the user sees on the checkout page for Nets Easy.', 'dibs-easy-for-woocommerce' ),
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
			'description' => __( 'Enter an URL to the icon you want to display for the payment method. Use <i>default</i> to display the default Nets logo. Leave blank to not show an icon at all.', 'dibs-easy-for-woocommerce' ),
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
	)
);
