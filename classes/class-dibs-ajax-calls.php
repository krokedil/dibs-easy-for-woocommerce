<?php

class DIBS_Ajax_Calls {
	public $private_key;

	function __construct() {
		add_action( 'wp_ajax_create_paymentID', array( $this, 'create_payment_id' ) );
		add_action( 'wp_ajax_nopriv_create_paymentID', array( $this, 'create_payment_id' ) );
		add_action( 'wp_ajax_payment_success', array( $this, 'get_order_data' ) );
		add_action( 'wp_ajax_nopriv_payment_success', array( $this, 'get_order_data' ) );
		add_action( 'wp_ajax_get_options', array( $this, 'get_options' ) );
		add_action( 'wp_ajax_nopriv_get_options', array( $this, 'get_options' ) );

		// Ajax to add order notes as a session for the customer
		add_action( 'wp_ajax_dibs_customer_order_note', array( $this, 'dibs_add_customer_order_note' ) );
		add_action( 'wp_ajax_nopriv_dibs_customer_order_note', array( $this, 'dibs_add_customer_order_note' ) );

		$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		$this->testmode = 'yes' === $dibs_settings['test_mode'];
		$this->private_key = $this->testmode ? $dibs_settings['dibs_test_checkout_key'] : $dibs_settings['dibs_checkout_key'];
	}

	public function create_payment_id() {
		// Create an empty WooCommerce order and get order id if one is not made already
		if ( WC()->session->get( 'dibs_incomplete_order' ) === null ) {
			$order    = wc_create_order();
			$order_id = $order->get_id();
			// Set the order id as a session variable
			WC()->session->set( 'dibs_incomplete_order', $order_id );
			$order->update_status( 'dibs-incomplete' );
			$order->save();
		} else {
			$order_id = WC()->session->get( 'dibs_incomplete_order' );
			$order = wc_get_order( $order_id );
			$order->update_status( 'dibs-incomplete' );
			$order->save();
		}

		$get_cart = new DIBS_Get_WC_Cart();

		// Get the datastring
		$datastring = $get_cart->create_cart( $order_id );
		// Make the request
		$request = new DIBS_Requests();
		$endpoint_sufix = 'payments/';
		$request = $request->make_request( 'POST', $datastring, $endpoint_sufix );
		if ( null != $request ) { // If array has a return
			if ( array_key_exists( 'paymentId', $request ) ) {
				// Create the return array
				$return               = array();
				$return['privateKey'] = $this->private_key;
				if ( 'sv_SE' === get_locale() ) {
					$language = 'sv-SE';
				} else {
					$language = 'en-GB';
				}
				$return['language']  = $language;
				$return['paymentId'] = $request;
				wp_send_json_success( $return );
				wp_die();
			} elseif ( array_key_exists( 'errors', $request ) ) {
				if ( array_key_exists( 'amount', $request->errors ) && 'Amount dosent match sum of orderitems' === $request->errors->amount[0] ) {
					$message = 'DIBS failed to create a Payment ID : ' . $request->errors->amount[0];
					wp_send_json_error( $this->fail_ajax_call( $order, $message ) );
					wp_die();
				}
			}
		} else { // If return array equals null
			wp_send_json_error( $this->fail_ajax_call( $order ) );
			wp_die();
		}
	}

	public function get_order_data() {
		$payment_id = $_POST['paymentId'];

		$order_id = WC()->session->get( 'dibs_incomplete_order' );

		WC()->session->set( 'order_awaiting_payment', $order_id );

		// Set the endpoint sufix
		$endpoint_sufix = 'payments/' . $payment_id;

		// Make the request
		$request = new DIBS_Requests();
		$request = $request->make_request( 'GET', '', $endpoint_sufix );

		// Get order id and update the hash for the order
		$order_id = WC()->session->get( 'order_awaiting_payment' );
		update_post_meta( $order_id, '_cart_hash', md5( wp_json_encode( wc_clean( WC()->cart->get_cart_for_session() ) ) . WC()->cart->total ) );
		$order = wc_get_order( $order_id );

		$order->update_status( 'pending' );
		
		// Convert country code from 3 to 2 letters 
		if( $request->payment->consumer->shippingAddress->country ) {
			$request->payment->consumer->shippingAddress->country = dibs_get_iso_2_country( $request->payment->consumer->shippingAddress->country );
		}
		
		// Set the paymentID as a meta value to be used later for reference
		update_post_meta( $order_id, '_dibs_payment_id', $payment_id );
		//$order->add_order_note( sprintf( __( 'Order made in DIBS with Payment ID %s', 'dibs-easy-for-woocommerce' ), $payment_id ) );
		wp_send_json_success( $request );
		wp_die();
	}

	// Function called if a ajax call does not receive the expected result
	public function fail_ajax_call( $order, $message = 'Failed to create an order with DIBS' ) {
		$order->add_order_note( sprintf( __( '%s', 'dibs-easy-for-woocommerce' ), $message ) );
	}
	public function get_options() {
		$return['privateKey'] = $this->private_key;
		if ( 'sv_SE' === get_locale() ) {
			$language = 'sv-SE';
		} else {
			$language = 'en-GB';
		}
		$return['language']  = $language;
		wp_send_json_success( $return );
		wp_die();
	}

	public function dibs_add_customer_order_note() {
		WC()->session->set( 'dibs_customer_order_note', $_POST['order_note'] );
		wp_send_json_success();
		wp_die();
	}
}
$dibs_ajax_calls = new DIBS_Ajax_Calls();
