<?php
namespace Krokedil\Nexi\Checkout;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces a Nexi payment that was submitted but never paid when the checkout reloads itself.
 */
class StalePaymentSession {
	/**
	 * The query arg the checkout scripts add when they reload the checkout.
	 */
	const RELOAD_ARG = 'nexi_new_payment';

	/**
	 * Check the payment before the checkout scripts are enqueued with it.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_replace_payment' ), 5 );
	}

	/**
	 * Drop the session's payment if it was submitted for an order that is still unpaid.
	 *
	 * Only runs after a reload by the checkout scripts, so a refresh, a second tab or a payment
	 * still in progress keeps its payment.
	 *
	 * @return void
	 */
	public static function maybe_replace_payment() {
		if ( empty( filter_input( INPUT_GET, self::RELOAD_ARG, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) ) {
			return;
		}

		if ( ! is_checkout() || is_wc_endpoint_url() ) {
			return;
		}

		// The customer is returning from a payment page, which the confirmation handles.
		if ( ! empty( filter_input( INPUT_GET, 'paymentid', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) || ! empty( filter_input( INPUT_GET, 'paymentId', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) ) {
			return;
		}

		$payment_id = WC()->session->get( 'dibs_payment_id' );
		$order      = wc_get_order( absint( WC()->session->get( 'order_awaiting_payment' ) ) );

		// The submit stores the payment on the order right before Nexi is told to take it.
		if ( ! empty( $payment_id ) && $order && $order->has_status( array( 'pending', 'failed' ) ) && $order->get_meta( '_dibs_payment_id' ) === $payment_id ) {
			$order_id = $order->get_id();
			\Nets_Easy_Logger::log( "[SESSION]: $order_id: The checkout was reloaded after the payment ($payment_id) was submitted. Checking it before the checkout reuses it." );

			// The payment went through after all, so confirm the order rather than take a second payment.
			if ( nexi_maybe_terminate_previous_payment_session( $order ) ) {
				wp_safe_redirect( add_query_arg( 'easy_confirm', 'yes', $order->get_checkout_order_received_url() ) );
				exit;
			}
		}

		// Drop the flag so a later refresh doesn't replace the payment again.
		wp_safe_redirect( remove_query_arg( self::RELOAD_ARG ) );
		exit;
	}
}
