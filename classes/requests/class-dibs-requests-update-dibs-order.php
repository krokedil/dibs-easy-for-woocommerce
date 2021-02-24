<?php
/**
 * Update order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Update order request class
 */
class DIBS_Requests_Update_DIBS_Order extends DIBS_Requests2 {

	/**
	 * $payment_id. Nets Payment ID.
	 *
	 * @var string
	 */
	public $payment_id;

	/**
	 * Class constructor.
	 *
	 * @param string $payment_id Nets payment id.
	 */
	public function __construct( $payment_id ) {
		$this->payment_id = $payment_id;
		parent::__construct();
	}

	/**
	 * Makes the request.
	 *
	 * @return mixed
	 */
	public function request() {
		$request_url  = $this->endpoint . 'payments/' . $this->payment_id . '/orderitems';
		$request_args = $this->get_request_args();
		$response     = wp_remote_request( $request_url, $this->get_request_args() );
		$code         = wp_remote_retrieve_response_code( $response );

		// Log the request.
		$log = Nets_Easy()->logger->format_log( $this->payment_id, 'PUT', 'Nets update cart', $request_args, $request_url, json_decode( wp_remote_retrieve_body( $response ), true ), $code );
		Nets_Easy()->logger->log( $log );

		if ( is_wp_error( $response ) ) {
			return $this->get_error_message( $response );
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			return 'SUCCESS';
		} else {
			return $this->get_error_message( $response );
		}
	}

	/**
	 * Gets the request args for the API call.
	 *
	 * @return array
	 */
	public function get_request_args() {
		$request_args = array(
			'headers'    => $this->request_headers(),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'PUT',
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
		return apply_filters( 'dibs_easy_update_order_args', DIBS_Requests_Order::get_order() );
	}
}
