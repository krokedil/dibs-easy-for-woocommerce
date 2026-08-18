<?php
/**
 * Settings for Express Checkout (Apple Pay / Google Pay).
 *
 * @package Nexi/ExpressCheckout
 */

namespace Krokedil\Nexi\ExpressCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class for Express Checkout.
 */
class Settings {

	/**
	 * The plugin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->settings = get_option( 'woocommerce_dibs_easy_settings', array() );

		add_filter( 'dibs_easy_settings', array( $this, 'add_settings_fields' ) );
	}

	/**
	 * Adds the Express Checkout settings fields to the Nexi Checkout settings screen.
	 *
	 * @param array $fields The existing settings fields.
	 * @return array
	 */
	public function add_settings_fields( $fields ) {
		$express_checkout_fields = array(
			'express_checkout_section'     => array(
				'id'          => 'express_checkout_section',
				'title'       => __( 'Express Checkout (Apple Pay / Google Pay)', 'dibs-easy-for-woocommerce' ),
				'type'        => 'krokedil_section_start',
				'description' => __( 'Show an Apple Pay / Google Pay express button that lets customers pay without going through the cart/checkout form. Requires Apple Pay/Google Pay to be enabled for your Nexi merchant account. Not available for subscription products.', 'dibs-easy-for-woocommerce' ),
			),
			'express_checkout_enabled'     => array(
				'title'   => __( 'Enable Express Checkout', 'dibs-easy-for-woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Show Apple Pay / Google Pay express buttons', 'dibs-easy-for-woocommerce' ),
				'default' => 'no',
			),
			'express_checkout_placement'   => array(
				'title'   => __( 'Button placement', 'dibs-easy-for-woocommerce' ),
				'type'    => 'select',
				'options' => array(
					'product' => __( 'Product page only', 'dibs-easy-for-woocommerce' ),
					'cart'    => __( 'Cart page only', 'dibs-easy-for-woocommerce' ),
					'both'    => __( 'Product and cart pages', 'dibs-easy-for-woocommerce' ),
				),
				'default' => 'both',
			),
			'express_checkout_section_end' => array(
				'type' => 'krokedil_section_end',
			),
		);

		return $this->insert_before( $fields, 'payment_method_split_section', $express_checkout_fields );
	}

	/**
	 * Inserts new fields before a given key in the fields array, falling back to appending them
	 * if the key isn't found.
	 *
	 * @param array  $fields The existing settings fields.
	 * @param string $before_key The key to insert the new fields before.
	 * @param array  $new_fields The new fields to insert.
	 * @return array
	 */
	private function insert_before( $fields, $before_key, $new_fields ) {
		$position = array_search( $before_key, array_keys( $fields ), true );
		if ( false === $position ) {
			return array_merge( $fields, $new_fields );
		}

		return array_merge(
			array_slice( $fields, 0, $position, true ),
			$new_fields,
			array_slice( $fields, $position, null, true )
		);
	}

	/**
	 * Checks if Express Checkout is enabled, i.e. the main Nexi Checkout gateway is enabled and
	 * Express Checkout itself has been turned on in the settings.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$gateway_enabled = 'yes' === ( $this->settings['enabled'] ?? 'no' );
		$express_enabled = 'yes' === ( $this->settings['express_checkout_enabled'] ?? 'no' );

		return $gateway_enabled && $express_enabled;
	}

	/**
	 * Gets the configured button placement.
	 *
	 * @return string One of 'product', 'cart', or 'both'.
	 */
	public function get_placement() {
		return $this->settings['express_checkout_placement'] ?? 'both';
	}

	/**
	 * Checks if test mode is enabled.
	 *
	 * @return bool
	 */
	public function is_test_mode() {
		return 'yes' === ( $this->settings['test_mode'] ?? 'no' );
	}

	/**
	 * Gets the Nexi Checkout key to use for the Express SDK.
	 *
	 * @return string
	 */
	public function get_checkout_key() {
		$key_name     = $this->is_test_mode() ? 'dibs_test_checkout_key' : 'dibs_checkout_key';
		$checkout_key = ! empty( $this->settings[ $key_name ] ) ? $this->settings[ $key_name ] : '';

		return apply_filters( 'nexi_request_checkout_key', $checkout_key, $this->is_test_mode() );
	}
}
