<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Requests_Create_DIBS_Order extends DIBS_Requests2 {

	public function __construct() {
		parent::__construct();
	}

	public function request() {
		$request_url = $this->endpoint . 'payments';

		$response = wp_remote_request( $request_url, $this->get_request_args() );
		if ( is_wp_error( $response ) ) {
			return $this->get_error_message( $response );
			//return 'ERROR';
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			return wp_remote_retrieve_body( $response );
		} else {
			return json_encode( $this->get_error_message( $response ) );
			//return wp_remote_retrieve_body( $response );
		}
	}

	public function get_request_args() {
		$request_args = array(
			'headers' => $this->request_headers(),
			'method'  => 'POST',
			'body'    => json_encode( $this->request_body() ),
		);
		DIBS_Easy::log( 'DIBS Create Order request args: ' . stripslashes_deep( json_encode( $request_args ) ) );
		return apply_filters( 'dibs_easy_create_order_args', $request_args );
	}
	public function request_body() {
		return array(
			'order'         => DIBS_Requests_Order::get_order(),
			'checkout'      => DIBS_Requests_Checkout::get_checkout(),
			'notifications' => DIBS_Requests_Notifications::get_notifications(),
		);
	}
}
