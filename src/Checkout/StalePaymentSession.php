<?php
namespace Krokedil\Nexi\Checkout;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces a Nexi payment that was submitted but never paid when the checkout loads again.
 */
class StalePaymentSession {
	/**
	 * Check the payment on every checkout page load.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_replace_payment' ) );
	}

	/**
	 * Drop the session's payment if it was submitted for an order that is still unpaid.
	 *
	 * @return void
	 */
	public static function maybe_replace_payment() {
		if ( ! is_checkout() || is_wc_endpoint_url() ) {
			return;
		}

		$payment_id = WC()->session->get( 'dibs_payment_id' );
		$order      = wc_get_order( absint( WC()->session->get( 'order_awaiting_payment' ) ) );
		if ( empty( $payment_id ) || ! $order || ! $order->has_status( array( 'pending', 'failed' ) ) ) {
			return;
		}

		// The submit stores the payment on the order right before Nexi is told to take it.
		if ( $order->get_meta( '_dibs_payment_id' ) !== $payment_id ) {
			return;
		}

		$order_id = $order->get_id();
		\Nets_Easy_Logger::log( "[SESSION]: $order_id: The checkout was loaded again after the payment ($payment_id) was submitted. Checking it before the checkout reuses it." );

		// The payment went through after all, so confirm the order rather than take a second payment.
		if ( nexi_maybe_terminate_previous_payment_session( $order ) ) {
			wp_safe_redirect( add_query_arg( 'easy_confirm', 'yes', $order->get_checkout_order_received_url() ) );
			exit;
		}
	}
}
