<?php
/**
 * Class for managing actions during the checkout process.
 *
 * @package Nets_Easy_For_WooCommerce/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for managing actions during the checkout process.
 */
class Nets_Easy_Checkout {
	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'update_nets_easy_order' ), 9999 );
	}

	/**
	 * Update the Nets Easy order after calculations from WooCommerce has run.
	 *
	 * @param WC_Cart $cart The WooCommerce cart.
	 * @return void
	 */
	public function update_nets_easy_order( $cart ) {

		$settings = get_option( 'woocommerce_dibs_easy_settings' );

		if ( ! is_checkout() && 'redirect' !== $settings['checkout_flow'] ) {
			return;
		}

		if ( 'dibs_easy' !== WC()->session->get( 'chosen_payment_method' ) ) {
			return;
		}

		$payment_id = WC()->session->get( 'dibs_payment_id' );
		if ( empty( $payment_id ) ) {
			return;
		}

		// Check if the cart hash has been changed since last update.
		$cart_hash  = WC()->cart->get_cart_hash();
		$saved_hash = WC()->session->get( 'nets_easy_last_update_hash' );

		// If they are the same, return.
		if ( $cart_hash === $saved_hash ) {
			return;
		}

		if ( WC()->session->get( 'nets_easy_last_shipping_total' ) === $cart->get_cart_shipping_total() ) {
			return;
		}

		// dibs_complete_payment_button_text.
		maybe_force_reload_btn_text();

		$nets_easy_order = Nets_Easy()->api->get_nets_easy_order( $payment_id );
		if ( ! is_wp_error( $nets_easy_order ) ) {
			Nets_Easy()->api->update_nets_easy_order( $payment_id );
		}
	}
} new Nets_Easy_Checkout();
