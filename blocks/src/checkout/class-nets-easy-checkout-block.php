<?php
/**
 * Nexi Checkout Block
 *
 * @package DIBS_Easy/Blocks
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;

/**
 * Class Nets_Easy_Checkout_Block
 */
class Nets_Easy_Checkout_Block extends AbstractPaymentMethodType {
	/**
	 * The payment method name.
	 *
	 * @var string
	 */
	protected $name = 'nets_easy';

	/**
	 * The payment methods to register.
	 *
	 * @var array
	 */
	protected $payment_methods;

	/**
	 * Class constructor.
	 *
	 * @param array $payment_methods The payment to register.
	 *
	 * @return void
	 */
	public function __construct( $payment_methods ) {
		$this->payment_methods = $payment_methods;
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$assets_path = dirname( __DIR__, 2 ) . '/build/checkout.asset.php';
		if ( file_exists( $assets_path ) ) {
			$assets = require $assets_path;
			wp_register_script( 'nets-easy-checkout-block', WC_DIBS__URL . '/blocks/build/checkout.js', $assets['dependencies'], $assets['version'], true );
		}
	}

	/**
	 * Loads the payment method scripts.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		return array( 'nets-easy-checkout-block' );
	}

	/**
	 * Gets the payment method data to load into the frontend.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$data     = array();
		$gateways = WC()->payment_gateways->payment_gateways();
		foreach ( $this->payment_methods as $id => $enabled ) {
			if ( ! $enabled ) {
				continue;
			}

			// Get the actual payment method from WooCommerce.
			$gateway = $gateways[ $id ];

			// Get the icon.
			$icon = $gateway->get_icon();

			// Get the url from the anchor tag.
			if ( preg_match( '/src="([^"]+)"/', $icon, $matches ) ) {
				$icon = $matches[1];
			}

			$data[ $id ]         = get_option( "woocommerce_{$id}_settings", array() );
			$data[ $id ]['icon'] = $icon;
		}

		return $data;
	}
}
