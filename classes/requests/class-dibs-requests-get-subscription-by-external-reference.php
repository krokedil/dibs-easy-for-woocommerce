<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Request_Get_Subscription_By_External_Reference extends DIBS_Requests2 {

	public $external_reference;
	public $order_id;

	public function __construct( $external_reference, $order_id ) {
		$this->external_reference = $external_reference;
		$this->order_id           = $order_id;
		parent::__construct();
	}

	public function request() {
		$request_url  = $this->endpoint . 'subscriptions?externalreference=' . $this->external_reference;
		$request_args = $this->get_request_args();
		DIBS_Easy::log( 'DIBS Get Subscription by externalreference args (' . $request_url . '): ' . stripslashes_deep( json_encode( $request_args ) ) );

		$response = wp_remote_request( $request_url, $this->get_request_args() );
		if ( is_wp_error( $response ) ) {
			return $this->get_error_message( $response );
			// return 'ERROR';
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			DIBS_Easy::log( 'DIBS Get Subscription by externalreference response: ' . stripslashes_deep( json_encode( $response ) ) );
			return json_decode( wp_remote_retrieve_body( $response ) );
		} else {
			return $this->get_error_message( $response );
			// return 'ERROR';
		}
	}

	public function get_request_args() {
		$request_args = array(
			'headers'    => $this->request_headers( $this->order_id ),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'GET',
			'timeout'    => apply_filters( 'nets_easy_set_timeout', 10 ),
		);
		return $request_args;
	}
}
