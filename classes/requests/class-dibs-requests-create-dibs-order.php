<?php
/**
 * Create new Nets order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Create new Nets order request class
 */
class DIBS_Requests_Create_DIBS_Order extends DIBS_Requests2 {

	/**
	 * Class constructor.
	 *
	 * @param string $checkout_flow The checkout flow selected in settings (or set in plugin for specific occations).
	 * @param string $order_id WC order id.
	 */
	public function __construct( $checkout_flow = 'embedded', $order_id = null ) {
		parent::__construct();
		$this->checkout_flow   = $checkout_flow;
		$this->order_id        = $order_id;
		$this->settings        = get_option( 'woocommerce_dibs_easy_settings' );
		$this->invoice_fee_id  = isset( $this->settings['dibs_invoice_fee'] ) ? $this->settings['dibs_invoice_fee'] : '';
		$this->merchant_number = isset( $this->settings['merchant_number'] ) ? $this->settings['merchant_number'] : '';
	}

	/**
	 * Makes the request.
	 *
	 * @return mixed
	 */
	public function request() {
		$request_url  = $this->endpoint . 'payments';
		$request_args = $this->get_request_args();
		$response     = wp_remote_request( $request_url, $request_args );
		$code         = wp_remote_retrieve_response_code( $response );

		// Log the request.
		$log = Nets_Easy()->logger->format_log( '', 'POST', 'Nets initialize payment', $request_args, $request_url, json_decode( wp_remote_retrieve_body( $response ), true ), $code );
		Nets_Easy()->logger->log( $log );

		if ( is_wp_error( $response ) ) {
			return wp_json_encode( $this->get_error_message( $response ) );
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			return wp_remote_retrieve_body( $response );
		} else {
			return wp_json_encode( $this->get_error_message( $response ) );
		}
	}

	/**
	 * Gets the request args for the API call.
	 *
	 * @return array
	 */
	public function get_request_args() {
		$request_args = array(
			'headers'    => $this->request_headers( $this->order_id ),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'POST',
			'body'       => wp_json_encode( $this->request_body() ),
			'timeout'    => apply_filters( 'nets_easy_set_timeout', 10 ),
		);
		return $request_args;
	}

	/**
	 * Gets the request request body.
	 *
	 * @return array
	 */
	public function request_body() {
		$request_args = array(
			'order'         => DIBS_Requests_Order::get_order( $this->checkout_flow, $this->order_id ),
			'checkout'      => DIBS_Requests_Checkout::get_checkout( $this->checkout_flow, $this->order_id ),
			'notifications' => DIBS_Requests_Notifications::get_notifications(),
		);

		if ( $this->invoice_fee_id ) {
			$request_args['paymentMethods'] = DIBS_Requests_Payment_Methods::get_invoice_fees();
		}

		if ( $this->merchant_number ) {
			$request_args['merchantNumber'] = $this->merchant_number;
		}

		return apply_filters( 'dibs_easy_create_order_args', $request_args );
	}
}
