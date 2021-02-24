<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Request_Get_Subscription_Bulk_Id extends DIBS_Requests2 {

	public $bulk_id;
	public $order_id;

	public function __construct( $bulk_id, $order_id ) {
		$this->bulk_id  = $bulk_id;
		$this->order_id = $order_id;
		parent::__construct();
	}

	public function request() {
		$request_url  = $this->endpoint . 'subscriptions/charges/' . $this->bulk_id;
		$request_args = $this->get_request_args();
		Nets_Easy()->logger->log( 'DIBS Get Subscription Bulk Charge ID args (' . $request_url . '): ' . stripslashes_deep( json_encode( $request_args ) ) );

		$response = wp_remote_request( $request_url, $this->get_request_args() );
		if ( is_wp_error( $response ) ) {
			return $this->get_error_message( $response );
			// return 'ERROR';
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			Nets_Easy()->logger->log( 'DIBS  Get Subscription Bulk Charge ID response: ' . stripslashes_deep( json_encode( $response ) ) );
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
