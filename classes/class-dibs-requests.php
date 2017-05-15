<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Requests {

	public $endpoint;

	public $key;

	public $testmode;

	public function __construct() {
		// Set the endpoint and key from settings
		$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		$this->testmode = 'yes' === $dibs_settings['test_mode'];
		$this->key = $this->testmode ? $dibs_settings['dibs_test_key'] : $dibs_settings['dibs_live_key'];
		$this->endpoint = $this->testmode ? 'https://test.api.dibspayment.eu/v1/' : 'https://checkout.dibspayment.eu/v1/';
	}

	public function make_request( $method, $body, $endpoint_suffix = '' ) {
		// Create the endpoint
		$endpoint = $this->endpoint . $endpoint_suffix;
		// Create the request array
		$request_array = array(
			'method'  => $method,
			'headers' => array(
				'Content-type'  => 'application/json',
				'Accept'        => 'application/json',
				'Authorization' => $this->key,
			),
		);
		// Check if body is needed and add the body if needed
		if ( '' != $body ) {
			$request_array['body'] = json_encode( $body, JSON_UNESCAPED_SLASHES );
		}

		// Make the request
		$response = wp_remote_request( $endpoint, $request_array );
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode( $response );
		return $response;
	}
}
$dibs_easy_requests = new DIBS_Requests();
