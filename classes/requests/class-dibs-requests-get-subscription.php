<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Requests_Get_Subscription extends DIBS_Requests2 {

	public $subscription_id;
	public $order_id;

	public function __construct( $subscription_id, $order_id ) {
		$this->subscription_id = $subscription_id;
		$this->order_id        = $order_id;
		parent::__construct();
	}

	public function request() {
		$request_url = $this->endpoint . 'subscriptions/' . $this->subscription_id;

		$response = wp_remote_request( $request_url, $this->get_request_args() );

		DIBS_Easy::log( 'DIBS GET Subscription response (' . $request_url . '): ' . stripslashes_deep( json_encode( $response ) ) );

		if ( is_wp_error( $response ) ) {
			$this->get_error_message( $response );
			return 'ERROR';
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			return json_decode( wp_remote_retrieve_body( $response ) );
		} else {
			$this->get_error_message( $response );
			return 'ERROR';
		}
	}

	public function get_request_args() {
		$request_args = array(
			'headers'    => $this->request_headers( $this->order_id ),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'GET',
		);
		DIBS_Easy::log( 'DIBS Get Subscription request args: ' . stripslashes_deep( json_encode( $request_args ) ) );

		return $request_args;
	}
}
