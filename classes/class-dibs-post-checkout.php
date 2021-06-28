<?php
/**
 * Nets Post Checkout (order management) class.
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Nets Post Checkout (order management) class.
 */
class DIBS_Post_Checkout {

	/**
	 * $manage_orders
	 *
	 * @var string
	 */
	public $manage_orders;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$dibs_settings       = get_option( 'woocommerce_dibs_easy_settings' );
		$this->manage_orders = isset( $dibs_settings['dibs_manage_orders'] ) ? $dibs_settings['dibs_manage_orders'] : '';
		if ( 'yes' === $this->manage_orders ) {
			add_action( 'woocommerce_order_status_completed', array( $this, 'dibs_order_completed' ) );
			add_action( 'woocommerce_order_status_cancelled', array( $this, 'dibs_order_canceled' ) );
		}
	}

	/**
	 * Charge / Activate order.
	 *
	 * @param  string $order_id WooCommerce order id.
	 */
	public function dibs_order_completed( $order_id ) {

		$wc_order = wc_get_order( $order_id );

		// Check if dibs was used to make the order.
		$gateway_used = get_post_meta( $order_id, '_payment_method', true );
		if ( 'dibs_easy' === $gateway_used ) {

			// Bail if the order hasn't been paid in DIBS yet.
			if ( empty( get_post_meta( $order_id, '_dibs_date_paid', true ) ) ) {
				return;
			}

			// Bail if we already have charged the order once in DIBS system.
			if ( get_post_meta( $order_id, '_dibs_charge_id', true ) ) {
				return;
			}

			$payment_type = get_post_meta( $order_id, 'dibs_payment_type', true );
			if ( 'A2A' === $payment_type ) {
				// This is a account to account purchase (like Swish). No activation is needed/possible.
				$dibs_payment_method = get_post_meta( $order_id, 'dibs_payment_method', true );
				/* Translators: Nets payment method for the order. */
				$wc_order->add_order_note( sprintf( __( 'No charge needed in Nets system since %s is a account to account payment.', 'dibs-easy-for-woocommerce' ), $dibs_payment_method ) );
				return;
			}

			// Bail if order total is 0. Can happen for 0 value initial subscription orders.
			if ( round( 0, 2 ) === round( $wc_order->get_total(), 2 ) ) {
				/* Translators: WC order total for the order. */
				$wc_order->add_order_note( sprintf( __( 'No charge needed in Nets system since the order total is %s.', 'dibs-easy-for-woocommerce' ), $wc_order->get_total() ) );
				return;
			}

			$request = new DIBS_Requests_Activate_Order( $order_id );
			$request = json_decode( $request->request() );

			// Error handling.
			if ( null !== $request ) {
				if ( isset( $request->chargeId ) ) { // Payment success.
					// Translators: Nets Charge ID.
					$wc_order->add_order_note( sprintf( __( 'Payment charged in Nets Easy with charge ID %s', 'dibs-easy-for-woocommerce' ), $request->chargeId ) ); // phpcs:ignore

					update_post_meta( $order_id, '_dibs_charge_id', $request->chargeId ); // phpcs:ignore
				} elseif ( isset( $request->errors ) ) { // Response with errors.
					if ( isset( $request->errors->instance ) ) { // If return is empty.
						$message = $request->errors->instance[0];
					} elseif ( isset( $request->errors->amount ) ) { // If total amount is wrong.
						$message = $request->errors->amount[0];
					} else {
						$message = wp_json_encode( $request->errors );
					}

					$this->charge_failed( $wc_order, true, $message );

				} elseif ( isset( $request->code ) && '1001' === $request->code ) { // Set order as completed if order has already been charged.
					// @todo - set status to on hold if WC order total and Nets order total don't match.
					$wc_order->add_order_note( sprintf( __( 'Nets error message: %s', 'dibs-easy-for-woocommerce' ), $request->message ) ); // phpcs:ignore
				} else {
					$this->charge_failed( $wc_order );
				}
			} else {
				$this->charge_failed( $wc_order );
			}
		}
	}

	/**
	 * Cancel order.
	 *
	 * @param  string $order_id WooCommerce order id.
	 */
	public function dibs_order_canceled( $order_id ) {
		// Check if dibs was used to make the order.
		$gateway_used = get_post_meta( $order_id, '_payment_method', true );
		if ( 'dibs_easy' === $gateway_used ) {

			// Don't do this if the order hasn't been paid in DIBS.
			if ( empty( get_post_meta( $order_id, '_dibs_date_paid', true ) ) ) {
				return;
			}

			$request = new DIBS_Requests_Cancel_Order( $order_id );
			$request = json_decode( $request->request() );

			$wc_order = wc_get_order( $order_id );

			if ( null === $request ) {
				$wc_order->add_order_note( sprintf( __( 'Order has been canceled in Nets', 'dibs-easy-for-woocommerce' ) ) );
			} else {
				if ( array_key_exists( 'errors', $request ) ) {
					$message = wp_json_encode( $request->errors );
				} elseif ( array_key_exists( 'message', $request ) ) {
					$message = wp_json_encode( $request->message );
				} else {
					$message = wp_json_encode( $request );
				}
				/* Translators: Nets message. */
				$wc_order->add_order_note( sprintf( __( 'There was a problem canceling the order in Nets: %s', 'dibs-easy-for-woocommerce' ), $message ) );
			}
		}
	}

	/**
	 * Function to handle a failed order.
	 *
	 * @param  object $order WooCommerce order.
	 * @param  bool   $fail Failed or not.
	 * @param  string $message Message for the order note.
	 */
	public function charge_failed( $order, $fail = true, $message = 'Payment failed in Nets' ) {
		/* Translators: Nets message. */
		$order->add_order_note( sprintf( __( 'Nets Error: %s', 'dibs-easy-for-woocommerce' ), $message ) );
		if ( true === $fail ) {
			$order->update_status( apply_filters( 'dibs_easy_failed_charge_status', 'on-hold', $order ) );
			$order->save();
		}
	}
}
