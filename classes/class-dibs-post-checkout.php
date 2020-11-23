<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Post_Checkout {

	public $manage_orders;

	public function __construct() {
		$dibs_settings       = get_option( 'woocommerce_dibs_easy_settings' );
		$this->manage_orders = $dibs_settings['dibs_manage_orders'];
		if ( 'yes' == $this->manage_orders ) {
			add_action( 'woocommerce_order_status_completed', array( $this, 'dibs_order_completed' ) );
			add_action( 'woocommerce_order_status_cancelled', array( $this, 'dibs_order_canceled' ) );
		}
	}

	public function dibs_order_completed( $order_id ) {

		$wc_order = wc_get_order( $order_id );

		// Check if dibs was used to make the order
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
				$wc_order->add_order_note( sprintf( __( 'No charge needed in Nets system since %s is a account to account payment.', 'dibs-easy-for-woocommerce' ), $dibs_payment_method ) );
				return;
			}

			$request = new DIBS_Requests_Activate_Order( $order_id );
			$request = json_decode( $request->request() );

			// Error handling.
			if ( null != $request ) {
				if ( array_key_exists( 'chargeId', $request ) ) { // Payment success.
					// Translators: Nets Charge ID.
					$wc_order->add_order_note( sprintf( __( 'Payment charged in Nets Easy with charge ID %s', 'dibs-easy-for-woocommerce' ), $request->chargeId ) );

					update_post_meta( $order_id, '_dibs_charge_id', $request->chargeId );
				} elseif ( array_key_exists( 'errors', $request ) ) { // Response with errors.
					if ( array_key_exists( 'instance', $request->errors ) ) { // If return is empty.
						$message = $request->errors->instance[0];
					} elseif ( array_key_exists( 'amount', $request->errors ) ) { // If total amount is wrong.
						$message = $request->errors->amount[0];
					} else {
						$message = wp_json_encode( $request->errors );
					}

					$this->charge_failed( $wc_order, true, $message );

				} elseif ( array_key_exists( 'code', $request ) && '1001' == $request->code ) { // Set order as completed if order has already been charged.
					$wc_order->add_order_note( __( 'Payment already charged in Nets', 'dibs-easy-for-woocommerce' ) );
				} else {
					$this->charge_failed( $wc_order );
				}
			} else {
				$this->charge_failed( $wc_order );
			}
		} // End if().
	}

	public function dibs_order_canceled( $order_id ) {
		// Check if dibs was used to make the order
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
					$message = json_encode( $request->errors );
				} elseif ( array_key_exists( 'message', $request ) ) {
					$message = json_encode( $request->message );
				} else {
					$message = json_encode( $request );
				}
				$wc_order->add_order_note( sprintf( __( 'There was a problem canceling the order in Nets: %s', 'dibs-easy-for-woocommerce' ), $message ) );
			}
		}
	}

	// Function to handle a failed order
	public function charge_failed( $order, $fail = true, $message = 'Payment failed in Nets' ) {
		$order->add_order_note( sprintf( __( 'Nets Error: %s', 'dibs-easy-for-woocommerce' ), $message ) );
		if ( true === $fail ) {
			$order->update_status( apply_filters( 'dibs_easy_failed_charge_status', 'on-hold', $order ) );
			$order->save();
		}
	}
}
$dibs_post_checkout = new DIBS_Post_Checkout();
