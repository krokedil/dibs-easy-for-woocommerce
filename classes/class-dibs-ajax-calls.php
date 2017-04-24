<?php

class DIBS_Ajax_Calls {
	public $endpoint;

	public $private_key;

	public $testmode;

	function __construct() {
		add_action( 'wp_ajax_create_paymentID', array( $this, 'create_payment_id' ) );
		add_action( 'wp_ajax_nopriv_create_paymentID', array( $this, 'create_payment_id' ) );
		add_action( 'wp_ajax_payment_success', array( $this, 'get_order_data' ) );
		add_action( 'wp_ajax_nopriv_payment_success', array( $this, 'get_order_data' ) );
		$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		$this->testmode     = 'yes' === $dibs_settings['test_mode'];
		$this->endpoint = $this->testmode ? $dibs_settings['dibs_test_key'] : $dibs_settings['dibs_live_key'];
		$this->private_key = $dibs_settings['dibs_private_key'];
	}
	public function create_payment_id() {
		// Create an empty WooCommerce order and get order id if one is not made already
		if ( WC()->session->get( 'order_awaiting_payment' ) === null ) {
			$order    = wc_create_order();
			$order_id = $order->get_order_number();
			// Set the order id as a session variable
			WC()->session->set( 'order_awaiting_payment', $order_id );
		} else {
			$order_id = WC()->session->get( 'order_awaiting_payment' );
			$order = wc_get_order( $order_id );
			$order->update_status( 'pending' );
		}

		$get_cart = new DIBS_Get_WC_Cart();

		// Get the datastring
		$datastring = $get_cart->create_cart( $order_id );
		// Make the request
		$request = new DIBS_Requests();
		$request = $request->make_request( 'POST', $datastring );

		// Create the return array
		$return = array();
		$return['privateKey'] = $this->private_key;
		if ( 'sv_SE' === get_locale() ) {
			$language = 'sv-SE';
		} else {
			$language = 'en-GB';
		}
		$return['language'] = $language;
		$return['paymentId'] = $request;

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

		// Set the paymentID as a meta value to be used later for reference
		update_post_meta( $order_id, '_dibs_payment_id', $payment_id );
		$order->add_order_note( sprintf( __( 'Order made in DIBS with Payment ID %s', 'woocommerce-dibs-easy' ), $payment_id ) );

		wp_send_json_success( $request );
		wp_die();
	}
}
$dibs_ajax_calls = new DIBS_Ajax_Calls();
