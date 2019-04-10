<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Request_Refund_Order extends DIBS_Requests2 {

	public $order_id;
	public $charge_id;

	public function __construct( $order_id ) {
		parent::__construct();

		$this->order_id = $order_id;
	}

	public function request() {
		$charge_id = get_post_meta( $this->order_id, '_dibs_charge_id', true );

		$request_url = $this->endpoint . 'charges/' . $charge_id . '/refunds';

		$response = wp_remote_request( $request_url, $this->get_request_args() );
		if ( is_wp_error( $response ) ) {
			$this->get_error_message( $response );
			return 'ERROR';
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			return wp_remote_retrieve_body( $response );
		} else {
			$this->get_error_message( $response );
			return 'ERROR';
		}
	}

	public function get_request_args() {
		$request_args = array(
			'headers'    => $this->request_headers( $this->order_id ),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'POST',
			'body'       => json_encode( $this->request_body() ),
		);
		DIBS_Easy::log( 'DIBS Refund Order request args: ' . stripslashes_deep( json_encode( $request_args ) ) );
		return apply_filters( 'dibs_easy_refund_order_args', $request_args );
	}

	public function request_body() {
		$order = wc_get_order( $this->order_id );
		return array(
			'amount'     => $order->get_total() * 100,
			'orderItems' => DIBS_Requests_Get_Order_Items::get_items( $this->order_id ),
		);
	}
}
