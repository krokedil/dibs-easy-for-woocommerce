<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Requests {

	public $endpoint;

	public $key;

	public $testmode;

	public static $log = false;

	public function __construct() {
		// Set the endpoint and key from settings
		$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		$this->testmode = 'yes' === $dibs_settings['test_mode'];
		$this->key = $this->testmode ? $dibs_settings['dibs_test_key'] : $dibs_settings['dibs_live_key'];
		$this->endpoint = $this->testmode ? 'https://test.api.dibspayment.eu/v1/' : 'https://api.dibspayment.eu/v1/';
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
		$this->log( 'Endpoint: ' . $endpoint . ' Request array to DIBS: ' . var_export( $request_array, true ) );
		// Make the request
		$response = wp_remote_request( $endpoint, $request_array );
		$this->log( 'Response from DIBS: ' . var_export( $response, true ) );
		if ( is_wp_error( $response ) ) {
			$this->log( 'Error connecting to DIBS' );
		} else {
			 $response = wp_remote_retrieve_body( $response );
			 $response = json_decode( $response );

			 return $response;
		}
	}

	public function get_order_fields( $payment_id ) {
		$order_id = WC()->session->get( 'dibs_incomplete_order' );

		// Set the endpoint sufix
		$endpoint_suffix = 'payments/' . $payment_id;

		// Make the request
		$request = $this->make_request( 'GET', '', $endpoint_suffix );
		return $request;
	}

	public static function log( $message ) {
		$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		if ( 'yes' === $dibs_settings['debug_mode'] ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->add( 'dibs_easy', $message );
		}
	}
}
$dibs_easy_requests = new DIBS_Requests();
