<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Request_Charge_Subscription extends DIBS_Requests2 {


	public function __construct( $order_id, $recurring_token ) {
		parent::__construct();
		$this->order_id        = $order_id;
		$this->recurring_token = $recurring_token;
	}

	public function request() {

		$request_url = $this->endpoint . 'subscriptions/' . $this->recurring_token . '/charges';

		$response = wp_remote_request( $request_url, $this->get_request_args() );
		if ( is_wp_error( $response ) ) {
			return $this->get_error_message( $response );
			// return 'ERROR';
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			DIBS_Easy::log( 'DIBS Charge subscription request response: ' . stripslashes_deep( json_encode( $response ) ) );
			return json_decode( wp_remote_retrieve_body( $response ) );
		} else {
			return $this->get_error_message( $response );
			// return 'ERROR';
		}
	}

	public function get_request_args() {
		$request_args = array(
			'headers'    => $this->request_headers(),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'POST',
			'body'       => json_encode( $this->request_body() ),
			'timeout'    => apply_filters( 'nets_easy_set_timeout', 10 ),
		);
		DIBS_Easy::log( 'DIBS Charge Subscription request args: ' . json_encode( $request_args ) );
		return apply_filters( 'dibs_easy_charge_subscription_args', $request_args );
	}

	public function request_body() {
		$order                      = wc_get_order( $this->order_id );
		$body                       = array();
		$body['order']['items']     = DIBS_Requests_Get_Order_Items::get_items( $this->order_id );
		$body['order']['amount']    = intval( round( $order->get_total() * 100 ) );
		$body['order']['currency']  = $order->get_currency();
		$body['order']['reference'] = $order->get_order_number();

		return $body;
	}
}
