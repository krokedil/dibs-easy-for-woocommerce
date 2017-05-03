<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Post_Checkout {
	public function __construct() {
		add_action( 'woocommerce_order_status_completed', array( $this, 'dibs_order_completed' ) );
	}

	public function dibs_order_completed( $order_id ) {
		//Get the order information
		$order = new DIBS_Get_WC_Cart();
		$body = $order->get_order_cart( $order_id );

		//Get paymentID from order meta and set endpoint
		$payment_id = get_post_meta( $order_id, '_dibs_payment_id' )[0];

		// Add the sufix to the endpoint
		$endpoint_sufix = 'payments/' . $payment_id . '/charges';

		// Make the request
		$request = new DIBS_Requests();
		$request = $request->make_request( 'POST', $body, $endpoint_sufix );

		$order = wc_get_order( $order_id );
		// Error handling
		if ( null != $request ) {
			if ( array_key_exists( 'chargeId', $request ) ) { // Payment success
				$order->add_order_note( sprintf( __( 'Payment made in DIBS with charge ID %s', 'woocommerce-dibs-easy' ), $request->chargeId ) );

				update_post_meta( $order_id, '_dibs_charge_id', $request->chargeId );
			} elseif ( array_key_exists( 'errors', $request ) ) { // Response with errors
				if ( array_key_exists( 'instance', $request->errors ) && 'cannot be null' === $request->errors->instance[0] ) { // If return is empty
					$this->charge_failed( $order, true );
				}
				if ( array_key_exists( 'amount', $request->errors ) && 'Amount must be greater than 0' === $request->errors->amount[0] ) { // If total amount equals 0
					$message = 'Total amount equal 0';
					$this->charge_failed( $order, true, $message );
				}
				if ( array_key_exists( 'amount', $request->errors ) && 'Amount dosen\'t match sum of orderitems' === $request->errors->amount[0] ) { // If the total amount does not equal the line items total
					$message = 'Order total amount does not match the order items';
					$this->charge_failed( $order, true, $message );
				}
			} elseif ( array_key_exists( 'code', $request ) && '1001' == $request->code ) { // Response with error code for overcharged order
				$message = 'Payment overcharged';
				$this->charge_failed( $order, false , $message );
			} else {
				$this->charge_failed( $order );
			}
		} else {
			$this->charge_failed( $order );
		}
	}

	// Function to handle a failed order
	public function charge_failed( $order, $fail = true, $message = 'Payment failed in DIBS' ) {
		$order->add_order_note( sprintf( __( 'DIBS Error: %s', 'woocommerce-dibs-easy' ), $message ) );
		if ( true === $fail ) {
			$order->update_status( 'processing' );
			$order->save();
		}
	}
}
$dibs_post_checkout = new DIBS_Post_Checkout();
