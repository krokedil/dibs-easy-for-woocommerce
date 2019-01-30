<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Requests_Activate_Order extends DIBS_Requests2 {

	public $order_id;

	public function __construct( $order_id ) {
		parent::__construct();

		$this->order_id = $order_id;
	}

	public function request() {

		$order       = wc_get_order( $order_id );
		$payment_id  = $order->get_transaction_id();
		$request_url = $this->endpoint . 'payments/' . $payment_id . '/charges';
		$response    = wp_remote_request( $request_url, $this->get_request_args() );

		if ( is_wp_error( $response ) ) {
			$this->get_error_message( $response );
			return 'ERROR';
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			return wp_remote_retrieve_body( $response );
		} else {
			$this->get_error_message( $response );
			return wp_remote_retrieve_body( $response );
			// return 'ERROR';
		}
	}

	public function get_request_args() {
		$request_args = array(
			'headers'    => $this->request_headers(),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'POST',
			'body'       => json_encode( $this->request_body() ),
		);
		DIBS_Easy::log( 'DIBS Activate Order request args: ' . json_encode( $request_args ) );
		return apply_filters( 'dibs_easy_activate_order_args', $request_args );
	}

	public function request_body() {
		$order = wc_get_order( $this->order_id );
		return array(
			'amount'     => intval( round( $order->get_total(), 2 ) * 100 ),
			'orderItems' => DIBS_Requests_Get_Order_Items::get_items( $this->order_id ),
		);
	}
}
