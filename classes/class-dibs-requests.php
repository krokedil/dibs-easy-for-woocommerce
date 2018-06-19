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
			'timeout' => 10,
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
			$error_message = $response->get_error_message();
			$this->log( 'Error connecting to DIBS. Error message: ' . $error_message );
		} else {
			 $response = wp_remote_retrieve_body( $response );
			 $response = json_decode( $response );
		}
		return $response;
	}

	public function get_order_fields( $payment_id ) {
		$order_id = WC()->session->get( 'dibs_incomplete_order' );

		// Set the endpoint sufix
		$endpoint_suffix = 'payments/' . $payment_id;

		// Make the request
		$request = $this->make_request( 'GET', '', $endpoint_suffix );
		return $request;
	}

	public function get_payment_id() {
		
		// Check if we should create a new payment ID or use an existing one
		if( isset( $_POST['dibs_payment_id'] ) && !empty( $_POST['dibs_payment_id'] ) ) {

			// This is a return from 3DSecure. Use the current payment ID
			$dibs_payment_id = sanitize_key( $_POST['dibs_payment_id'] );
			
		} else {

			// This is a new order
			// Set DIBS Easy as the chosen payment method
			WC()->session->set( 'chosen_payment_method', 'dibs_easy' );
			
			// Create an empty WooCommerce order and get order id if one is not made already
			if ( WC()->session->get( 'dibs_incomplete_order' ) === null ) {
				$order    = wc_create_order();
				$order_id = $order->get_id();
				// Set the order id as a session variable
				WC()->session->set( 'dibs_incomplete_order', $order_id );
				$order->update_status( 'dibs-incomplete' );
				$order->save();
			} else {
				$order_id = WC()->session->get( 'dibs_incomplete_order' );
				$order = wc_get_order( $order_id );
				$order->update_status( 'dibs-incomplete' );
				$order->save();
			}

			$get_cart = new DIBS_Get_WC_Cart();

			// Get the datastring
			$datastring = $get_cart->create_cart( $order_id );
			// Make the request
			$request = new DIBS_Requests();
			$endpoint_sufix = 'payments/';
			$response = $request->make_request( 'POST', $datastring, $endpoint_sufix );

			if( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
				$order->add_order_note( sprintf( __( 'Could not connect to DIBS: %s', 'dibs-easy-for-woocommerce' ), $message ) );
				$dibs_payment_id = new WP_Error( 'error', sprintf( __( 'Could not connect to DIBS: %s', 'dibs-easy-for-woocommerce' ), $message ) );
			} elseif( empty( $response ) ) {
				$order->add_order_note( sprintf( __( 'No response when connecting to DIBS:', 'dibs-easy-for-woocommerce' ), $message ) );
				$dibs_payment_id = new WP_Error( 'error', sprintf( __( 'No response when connecting to DIBS: %s', 'dibs-easy-for-woocommerce' ), $message ) );
			} elseif( array_key_exists( 'errors', $response ) ) {
				$message = var_export( $response->errors, true );
				$order->add_order_note( sprintf( __( 'Connection error: %s', 'dibs-easy-for-woocommerce' ), $message ) );
				$dibs_payment_id = new WP_Error( 'error', sprintf( __( 'Connection error: %s', 'dibs-easy-for-woocommerce' ), $message ) );
			} else {
				$dibs_payment_id = sanitize_key( $response->paymentId );
			}
		}

		return $dibs_payment_id;
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
