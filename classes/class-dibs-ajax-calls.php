<?php

class DIBS_Ajax_Calls {
	function __construct() {
		add_action( 'wp_ajax_create_paymentID', array($this, 'create_paymentID') );
		add_action( 'wp_ajax_nopriv_create_paymentID', array($this, 'create_paymentID') );
		add_action( 'wp_ajax_payment_success', array($this, 'get_order_data') );
		add_action( 'wp_ajax_nopriv_payment_success', array($this, 'get_order_data') );
	}
	public function create_paymentID() {
		// Create an empty WooCommerce order and get order id if one is not made already
		if (WC()->session->get( 'order_awaiting_payment') === null) {
			$order   = wc_create_order();
			$orderID = $order->get_order_number();
			// Set the order id as a session variable
			WC()->session->set( 'order_awaiting_payment', $orderID );
		}else {
			$orderID = WC()->session->get( 'order_awaiting_payment' );
			$order = wc_get_order( $orderID );
			$order->update_status( 'pending' );
		}

		$get_cart = new DIBS_Get_WC_Cart();
		$datastring = $get_cart->createCart($orderID);
		$gateway = new DIBS_Easy_Gateway();
		$response = wp_remote_request( $gateway->endpoint, array(
				'method'  => 'POST',
				'headers' => array(
					"Content-type"  => "application/json",
					"Accept"        => "application/json",
					"Authorization" => $gateway->key,
				),
				'body'    => $datastring,
			)
		);
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode($response);
		$return = array();
		$return['privateKey'] = $gateway->private_key;
		$return['language'] = $gateway->language;
		$return['paymentId'] = $response;

		// Set the paymentID as a meta value to be used later for reference
		update_post_meta( $orderID, '_paymentID', $response->paymentId);
		$order->add_order_note( sprintf( __( 'Order made in DIBS with Payment ID ' . $response->paymentId, 'woocommerce-dibs-easy' )) );

		wp_send_json_success( $return );
		wp_die();
	}
	public function get_order_data(){
		$paymentID = $_POST['paymentId'];
		$gateway = new DIBS_Easy_Gateway();
		$url = $gateway->endpoint . '/' . $paymentID;
		$response = wp_remote_request( $url, array(
				'method'  => 'GET',
				'headers' => array(
					"Content-type"  => "application/json",
					"Accept"        => "application/json",
					"Authorization" => $gateway->key,
				),
			)
		);

		// Get order id and update the hash for the order
		$orderID = WC()->session->get( 'order_awaiting_payment' );
		update_post_meta( $orderID, '_cart_hash', md5( wp_json_encode( wc_clean( WC()->cart->get_cart_for_session() ) ) . WC()->cart->total ) );
		$order = wc_get_order( $orderID );
		$order->add_order_note( sprintf( __( 'Order is awaiting completion and payment has been reserved in DIBS', 'woocommerce-dibs-easy' )) );

		$response = wp_remote_retrieve_body( $response );
		$response = json_decode($response);
		wp_send_json_success( $response );
		wp_die();
	}
}
$dibs_ajax_calls = new DIBS_Ajax_Calls();