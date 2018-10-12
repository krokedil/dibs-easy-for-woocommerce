<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Requests2 {

	public $key;
	public $endpoint;

	public function __construct() {
		$dibs_settings  = get_option( 'woocommerce_dibs_easy_settings' );
		$testmode       = 'yes' === $dibs_settings['test_mode'];
		$this->key      = $testmode ? $dibs_settings['dibs_test_key'] : $dibs_settings['dibs_live_key'];
		$this->endpoint = $testmode ? DIBS_API_TEST_ENDPOINT : DIBS_API_LIVE_ENDPOINT;
	}
	public function request() {
		die( 'function DIBS_Requests::request() must be over-ridden in a sub-class.' );
	}
	public function request_headers() {
		$get_header = new DIBS_Requests_Header();
		return $get_header->get();
	}
	public function request_body() {
		die( 'function Collector_Checkout_Requests::request_body() must be over-ridden in a sub-class.' );
	}
	public function get_error_message( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$errors        = new WP_Error();
		foreach ( $response_body->errors as $key => $value ) {
			$errors->add( 'dibs_easy', $value );
		}
		DIBS_Easy::log( 'DIBS Error Response: ' . json_encode( $response_body ) );
		return $errors;
	}
}
