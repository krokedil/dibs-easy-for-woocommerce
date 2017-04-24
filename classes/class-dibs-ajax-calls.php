<?php

class DIBS_Ajax_Calls {
	function __construct() {
		add_action( 'wp_ajax_create_paymentID', array( $this, 'create_payment_id' ) );
		add_action( 'wp_ajax_nopriv_create_paymentID', array( $this, 'create_payment_id' ) );
		add_action( 'wp_ajax_payment_success', array( $this, 'get_order_data' ) );
		add_action( 'wp_ajax_nopriv_payment_success', array( $this, 'get_order_data' ) );
	}
	public function create_payment_id() {
		// Create an empty WooCommerce order and get order id if one is not made already
		if ( WC()->session->get( 'order_awaiting_payment' ) === null ) {
			$order   = wc_create_order();
			$order_id = $order->get_order_number();
			// Set the order id as a session variable
			WC()->session->set( 'order_awaiting_payment', $order_id );
		} else {
			$order_id = WC()->session->get( 'order_awaiting_payment' );
			$order = wc_get_order( $order_id );
			$order->update_status( 'pending' );
		}

		$get_cart = new DIBS_Get_WC_Cart();
		$gateway = new DIBS_Easy_Gateway();

		// Get the datastring
		$datastring = $get_cart->create_cart( $order_id );
		// Make the request
		$request = new DIBS_Requests();
		$request = $request->make_request( 'POST', $datastring );

		// Create the return array
		$return = array();
		$return['privateKey'] = $gateway->private_key;
		$return['language'] = $gateway->language;
		$return['paymentId'] = $request;

		// Set the paymentID as a meta value to be used later for reference
		update_post_meta( $order_id, '_paymentID', $request->paymentId );
		$order->add_order_note( sprintf( __( 'Order made in DIBS with Payment ID ' . $request->paymentId, 'woocommerce-dibs-easy' ) ) );

		wp_send_json_success( $return );
		wp_die();
	}
	public function get_order_data() {
		$payment_id = $_POST['paymentId'];

		// Set the endpoint sufix
		$endpoint_sufix = '/' . $payment_id;

		// Make the request
		$request = new DIBS_Requests();
		$request = $request->make_request( 'GET', '', $endpoint_sufix );

		// Get order id and update the hash for the order
		$order_id = WC()->session->get( 'order_awaiting_payment' );
		update_post_meta( $order_id, '_cart_hash', md5( wp_json_encode( wc_clean( WC()->cart->get_cart_for_session() ) ) . WC()->cart->total ) );
		$order = wc_get_order( $order_id );
		$order->add_order_note( sprintf( __( 'Order is awaiting completion and payment has been reserved in DIBS', 'woocommerce-dibs-easy' ) ) );

		wp_send_json_success( $request );
		wp_die();
	}
}
$dibs_ajax_calls = new DIBS_Ajax_Calls();
