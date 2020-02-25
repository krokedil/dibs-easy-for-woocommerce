<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
class DIBS_Requests_Create_DIBS_Order extends DIBS_Requests2 {

	public function __construct( $checkout_flow = 'embedded', $order_id = null ) {
		parent::__construct();
		$this->checkout_flow = $checkout_flow;
		$this->order_id      = $order_id;
	}

	public function request() {
		$request_url = $this->endpoint . 'payments';
		$response    = wp_remote_request( $request_url, $this->get_request_args() );
		DIBS_Easy::log( 'DIBS Create Order request response: ' . stripslashes_deep( wp_json_encode( $response ) ) );
		if ( is_wp_error( $response ) ) {
			return $this->get_error_message( $response );
			// return 'ERROR';
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			return wp_remote_retrieve_body( $response );
		} else {
			return json_encode( $this->get_error_message( $response ) );
			// return wp_remote_retrieve_body( $response );
		}
	}

	public function get_request_args() {
		$request_args = array(
			'headers'    => $this->request_headers( $this->order_id ),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'POST',
			'body'       => json_encode( $this->request_body() ),
			'timeout'    => apply_filters( 'nets_easy_set_timeout', 10 ),
		);
		DIBS_Easy::log( 'DIBS Create Order request args: ' . stripslashes_deep( json_encode( $request_args ) ) );
		return $request_args;
	}
	public function request_body() {
		$request_args = array(
			'order'         => DIBS_Requests_Order::get_order( $this->checkout_flow, $this->order_id ),
			'checkout'      => DIBS_Requests_Checkout::get_checkout( $this->checkout_flow, $this->order_id ),
			'notifications' => DIBS_Requests_Notifications::get_notifications(),
		);
		if ( 'embedded' === $this->checkout_flow ) {
			$request_args['paymentMethods'] = DIBS_Requests_Payment_Methods::get_invoice_fees();
		}
		return apply_filters( 'dibs_easy_create_order_args', $request_args );
	}
}
