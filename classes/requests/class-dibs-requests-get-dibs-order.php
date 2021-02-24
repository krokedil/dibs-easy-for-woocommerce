<?php
/**
 * Get Nets order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Nets order request class
 */
class DIBS_Requests_Get_DIBS_Order extends DIBS_Requests2 {

	/**
	 * $payment_id.
	 *
	 * @var string
	 */
	public $payment_id;

	/**
	 * $order_id.
	 *
	 * @var string
	 */
	public $order_id;

	/**
	 * Class constructor.
	 *
	 * @param string $payment_id The Nets Easy payment id.
	 * @param string $order_id WC order id.
	 */
	public function __construct( $payment_id, $order_id = null ) {
		$this->payment_id = $payment_id;
		$this->order_id   = $order_id;
		parent::__construct();
	}

	/**
	 * Makes the request.
	 *
	 * @return mixed
	 */
	public function request() {
		$request_url  = $this->endpoint . 'payments/' . $this->payment_id;
		$request_args = $this->get_request_args();
		$response     = wp_remote_request( $request_url, $request_args );
		$code         = wp_remote_retrieve_response_code( $response );

		// Log the request.
		$log = Nets_Easy()->logger->format_log( $this->payment_id, 'GET', 'Nets get checkout', $request_args, $request_url, json_decode( wp_remote_retrieve_body( $response ), true ), $code );
		Nets_Easy()->logger->log( $log );

		if ( is_wp_error( $response ) ) {
			return $this->get_error_message( $response );
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			return json_decode( wp_remote_retrieve_body( $response ) );
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
			'headers'    => $this->request_headers( $this->order_id ),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'GET',
			'timeout'    => apply_filters( 'nets_easy_set_timeout', 10 ),
		);
		return $request_args;
	}
}
