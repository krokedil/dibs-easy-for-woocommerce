<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Post_Checkout{
	public function __construct() {
		add_action( 'woocommerce_order_status_completed', array( $this, 'dibs_order_completed' ) );
	}

	public function dibs_order_completed( $order_id ) {
		//Get the order information
		$order = new DIBS_Get_WC_Cart();
		$body = $order->get_order_cart( $order_id );

		//Get paymentID from order meta and set endpoint
		$payment_id = get_post_meta( $order_id, '_paymentID' )[0];

		// Add the sufix to the endpoint
		$endpoint_sufix = '/' . $payment_id . '/charges';

		// Make the request
		$request = new DIBS_Requests();
		$request = $request->make_request( 'POST', $body, $endpoint_sufix );

		$order = wc_get_order( $order_id );
		$order->add_order_note( sprintf( __( 'Payment made in DIBS with charge ID ' . $request->chargeId, 'woocommerce-dibs-easy' ) ) );
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		// Refund the order here
	}
}
$dibs_post_checkout = new DIBS_Post_Checkout();
