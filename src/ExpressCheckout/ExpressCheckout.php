<?php
/**
 * Express Checkout (Apple Pay / Google Pay) button on product and cart pages.
 *
 * @package Nexi/ExpressCheckout
 */

namespace Krokedil\Nexi\ExpressCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers hooks and renders the Express Checkout button.
 */
class ExpressCheckout {

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

		if ( ! $this->settings->is_enabled() ) {
			return;
		}

		$placement = $this->settings->get_placement();

		if ( in_array( $placement, array( 'product', 'both' ), true ) ) {
			add_action( 'woocommerce_single_product_summary', array( $this, 'render_product_button' ), 31 );
		}

		if ( in_array( $placement, array( 'cart', 'both' ), true ) ) {
			add_action( 'woocommerce_proceed_to_checkout', array( $this, 'render_cart_button' ), 25 );
		}

		new Assets( $this->settings );
		new AJAX();
	}

	/**
	 * Renders the Express Checkout button container on the single product page.
	 *
	 * @return void
	 */
	public function render_product_button() {
		global $product;

		if ( ! $product instanceof \WC_Product || did_action( 'woocommerce_single_product_summary' ) > 1 ) {
			return;
		}

		if ( ! $this->is_product_eligible( $product ) ) {
			return;
		}

		echo '<div id="nexi-express-button-product" class="nexi-express-button-container" data-product-id="' . esc_attr( $product->get_id() ) . '"></div>';
	}

	/**
	 * Renders the Express Checkout button container on the cart page.
	 *
	 * @return void
	 */
	public function render_cart_button() {
		if ( ! $this->is_cart_eligible() ) {
			return;
		}

		echo '<div id="nexi-express-button-cart" class="nexi-express-button-container"></div>';
	}

	/**
	 * Checks if a product is eligible for the Express Checkout button: purchasable, in stock, and
	 * not a subscription (Nexi Checkout's Express Buttons do not support subscriptions).
	 *
	 * @param \WC_Product $product The product.
	 * @return bool
	 */
	private function is_product_eligible( \WC_Product $product ) {
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return false;
		}

		return ! self::is_subscription_product( $product );
	}

	/**
	 * Checks if the cart is eligible for the Express Checkout button: not empty, and none of its
	 * line items are a subscription product.
	 *
	 * @return bool
	 */
	private function is_cart_eligible() {
		if ( WC()->cart->is_empty() ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( self::is_subscription_product( $cart_item['data'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if a product is a WooCommerce Subscriptions subscription product. Express Checkout
	 * does not support subscriptions, a limitation of Nexi Checkout's Express Buttons.
	 *
	 * @param \WC_Product $product The product.
	 * @return bool
	 */
	public static function is_subscription_product( $product ) {
		return class_exists( 'WC_Subscriptions_Product' ) && \WC_Subscriptions_Product::is_subscription( $product );
	}
}
