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

		if ( ! is_checkout() ) {
			return;
		}

		if ( 'redirect' === $settings['checkout_flow'] ) {
			return;
		}

		if ( 'dibs_easy' !== WC()->session->get( 'chosen_payment_method' ) ) {
			return;
		}

		$payment_id = WC()->session->get( 'dibs_payment_id' );
		if ( empty( $payment_id ) ) {
			return;
		}

		// Check that the currency is the same as earlier, otherwise create a new session.
		if ( get_woocommerce_currency() !== WC()->session->get( 'nets_easy_currency' ) ) {
			wc_dibs_unset_sessions();
			Nets_Easy_Logger::log( 'Currency changed in update Nets function. Clearing Nets session and reloading the checkout page.' );
			WC()->session->reload_checkout = true;
			return;
		}

		// Check if the cart hash has been changed since last update.
		$cart_hash  = $cart->get_cart_hash();
		$saved_hash = WC()->session->get( 'nets_easy_last_update_hash' );

		// If they are the same, return.
		if ( $cart_hash === $saved_hash ) {
			return;
		}

		// Check if we have a case where a regular product is in the cart and the incorrect text on the button.
		// If so, delete the session and reload the page.
		if ( isset( WC()->session ) && method_exists( WC()->session, 'get' ) ) {
			if ( WC()->session->get( 'dibs_cart_contains_subscription' ) !== get_dibs_cart_contains_subscription() ) {
				wc_dibs_unset_sessions();
				if ( wp_doing_ajax() ) {
					WC()->session->reload_checkout = true;
				} else {
					wp_safe_redirect( wc_get_checkout_url() );
				}
			}
		}

		// Retrieves the order.
		$nets_easy_order = Nets_Easy()->api->get_nets_easy_order( $payment_id );
		if ( ! is_wp_error( $nets_easy_order ) ) {
			// Updates the order.
			Nets_Easy()->api->update_nets_easy_order( $payment_id );
		}
		// Update the session value with the new cart hash.
		WC()->session->set( 'nets_easy_last_update_hash', $cart_hash );
	}
} new Nets_Easy_Checkout();
