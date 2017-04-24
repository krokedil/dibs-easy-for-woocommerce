<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Requests {

	public $endpoint;

	public $key;

	public function __construct() {
		$gateway = new DIBS_Easy_Gateway();
		// Set the endpoint and key from settings
		$this->endpoint = $gateway->endpoint;
		$this->key = $gateway->key;
	}

	public function make_request( $method, $body, $endpoint_sufix = '' ) {
		// Create the endpoint
		$endpoint = $this->endpoint . $endpoint_sufix;
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
