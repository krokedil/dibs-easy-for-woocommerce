<?php
/**
 * Enqueues Express Button assets on single product pages.
 *
 * @package Krokedil\NexiCheckout\ExpressButton
 */

namespace Krokedil\Nexi\ExpressButton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles script/style enqueuing for the Express Button.
 */
class Assets {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'render_container' ] );
	}

	/**
	 * Enqueues the Express SDK and plugin script on single product pages.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! is_product() ) {
			return;
		}

		$settings  = get_option( 'woocommerce_dibs_easy_settings', [] );
		$test_mode = 'yes' === ( $settings['test_mode'] ?? 'no' );

		$sdk_url = $test_mode
			? 'https://test.checkout.dibspayment.eu/express/sdk.js'
			: 'https://checkout.dibspayment.eu/express/sdk.js';

		wp_enqueue_script(
			'nexi-express-sdk',
			$sdk_url,
			[],
			null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- external, versioned by Nexi
			false
		);

		wp_enqueue_script(
			'nexi-express-button',
			WC_DIBS__URL . '/assets/js/nexi-express-button.js',
			[ 'nexi-express-sdk' ],
			WC_DIBS_EASY_VERSION,
			true
		);

		$checkout_key = $this->get_checkout_key( $settings, $test_mode );

		wp_localize_script(
			'nexi-express-button',
			'nexiExpressParams',
			[
				'checkoutKey'            => $checkout_key,
				'locale'                 => wc_dibs_get_locale(),
				'createPaymentUrl'       => \WC_AJAX::get_endpoint( 'nexi_express_create_payment' ),
				'shippingUpdateUrl'      => \WC_AJAX::get_endpoint( 'nexi_express_shipping_update' ),
				'paymentCompleteUrl'     => \WC_AJAX::get_endpoint( 'nexi_express_payment_complete' ),
				'nonce'                  => wp_create_nonce( 'nexi_express' ),
				'debug'                  => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			]
		);
	}

	/**
	 * Renders the Express Button container div on single product pages.
	 *
	 * @return void
	 */
	public function render_container(): void {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		printf(
			'<div id="nexi-express-button-container" data-product-id="%s" style="margin-top:1em;"></div>',
			esc_attr( (string) $product->get_id() )
		);
	}

	/**
	 * Returns the correct checkout key based on test mode.
	 *
	 * @param array $settings Plugin settings.
	 * @param bool  $test_mode Whether test mode is active.
	 * @return string
	 */
	private function get_checkout_key( array $settings, bool $test_mode ): string {
		$key_name = $test_mode ? 'dibs_test_checkout_key' : 'dibs_checkout_key';
		$key      = $settings[ $key_name ] ?? '';
		return apply_filters( 'nexi_request_checkout_key', $key, $test_mode );
	}
}
