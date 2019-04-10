<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Requests2 {

	public $key;
	public $endpoint;

	public function __construct() {
		$dibs_settings  = get_option( 'woocommerce_dibs_easy_settings' );
		$this->testmode = 'yes' === $dibs_settings['test_mode'];
		$this->key      = $this->testmode ? $dibs_settings['dibs_test_key'] : $dibs_settings['dibs_live_key'];
		$this->endpoint = $this->testmode ? DIBS_API_TEST_ENDPOINT : DIBS_API_LIVE_ENDPOINT;
	}
	public function request() {
		die( 'function DIBS_Requests::request() must be over-ridden in a sub-class.' );
	}
	public function request_headers( $order_id = null ) {
		$get_header = new DIBS_Requests_Header();
		return $get_header->get( $order_id );
	}
	public function request_user_agent() {
		$get_user_agent = new DIBS_Requests_User_Agent();
		return $get_user_agent->get();
	}
	public function request_body() {
		die( 'function DIBS_Requests::request_body() must be over-ridden in a sub-class.' );
	}
	public function get_error_message( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );
		if ( empty( $response_body ) ) {
			$response_body = 'Response code ' . $response['response']['code'] . '. Message: ' . $response['response']['message'];
		}

		$errors = new WP_Error();
		$errors->add( 'dibs_easy', $response_body );

		DIBS_Easy::log( 'DIBS Error Response: ' . stripslashes_deep( json_encode( $response_body ) ) );
		return $errors;
	}
}
