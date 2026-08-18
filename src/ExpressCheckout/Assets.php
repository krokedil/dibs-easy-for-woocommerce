<?php
/**
 * Asset loading for Express Checkout (Apple Pay / Google Pay).
 *
 * @package Nexi/ExpressCheckout
 */

namespace Krokedil\Nexi\ExpressCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues the Nexi Express SDK and the plugin's Express Checkout script.
 */
class Assets {

	/**
	 * The Express Checkout settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Class constructor.
	 *
	 * @param Settings $settings The Express Checkout settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
	}

	/**
	 * Enqueues the Express SDK and script on eligible pages: the product and cart pages (where the
	 * button renders), and the checkout page but only once an Express payment has actually been
	 * completed (there is nothing for this script to do on an ordinary checkout page visit).
	 *
	 * @return void
	 */
	public function maybe_enqueue() {
		if ( ! $this->settings->is_enabled() ) {
			return;
		}

		$is_checkout_finalize = is_checkout() && ! is_order_received_page() && nexi_express_checkout_is_completed_for_current_cart();

		if ( ! is_product() && ! is_cart() && ! $is_checkout_finalize ) {
			return;
		}

		$sdk_url = $this->settings->is_test_mode()
			? 'https://test.checkout.dibspayment.eu/express/sdk.js'
			: 'https://checkout.dibspayment.eu/express/sdk.js';

		wp_enqueue_script( 'nexi-express-sdk', $sdk_url, array(), WC_DIBS_EASY_VERSION, true );

		wp_enqueue_script(
			'nexi-express-checkout',
			WC_DIBS__URL . '/assets/js/nets-easy-express.min.js',
			array( 'jquery', 'nexi-express-sdk' ),
			WC_DIBS_EASY_VERSION,
			true
		);

		wp_localize_script(
			'nexi-express-checkout',
			'nexiExpressParams',
			array(
				'checkoutKey'        => $this->settings->get_checkout_key(),
				'locale'             => wc_dibs_get_locale(),
				'isProductPage'      => is_product(),
				'isCartPage'         => is_cart(),
				'isCheckoutFinalize' => $is_checkout_finalize,
				'paymentId'          => $is_checkout_finalize ? WC()->session->get( 'dibs_payment_id' ) : '',
				'createSessionUrl'   => \WC_AJAX::get_endpoint( 'nexi_express_create_session' ),
				'shippingChangeUrl'  => \WC_AJAX::get_endpoint( 'nexi_express_shipping_address_change' ),
				'paymentCompleteUrl' => \WC_AJAX::get_endpoint( 'nexi_express_payment_completed' ),
				'getOrderDataUrl'    => \WC_AJAX::get_endpoint( 'get_order_data' ),
				'checkoutSubmitUrl'  => \WC_AJAX::get_endpoint( 'checkout' ),
				'nonce'              => wp_create_nonce( AJAX::NONCE_ACTION ),
				'checkoutNonce'      => wp_create_nonce( 'nets_checkout' ),
				'debug'              => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			)
		);
	}
}
