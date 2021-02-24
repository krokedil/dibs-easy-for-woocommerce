<?php
/**
 * Activate order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Activate order request class
 */
class DIBS_Requests_Activate_Order extends DIBS_Requests2 {

	/**
	 * Reference to order_id.
	 *
	 * @var $order_id
	 */
	public $order_id;

	/**
	 * Class constructor.
	 *
	 * @param string $order_id WC order id.
	 */
	public function __construct( $order_id ) {
		parent::__construct();

		$this->order_id = $order_id;
	}

	/**
	 * Makes the request.
	 *
	 * @return mixed
	 */
	public function request() {

		$order        = wc_get_order( $this->order_id );
		$payment_id   = $order->get_transaction_id();
		$request_url  = $this->endpoint . 'payments/' . $payment_id . '/charges';
		$request_args = $this->get_request_args();
		$response     = wp_remote_request( $request_url, $request_args );
		$code         = wp_remote_retrieve_response_code( $response );

		// Log the request.
		$log = Nets_Easy()->logger->format_log( $payment_id, 'POST', 'Nets activate order', $request_args, $request_url, json_decode( wp_remote_retrieve_body( $response ), true ), $code );
		Nets_Easy()->logger->log( $log );

		if ( is_wp_error( $response ) ) {
			$this->get_error_message( $response );
			return 'ERROR';
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			return wp_remote_retrieve_body( $response );
		} else {
			$this->get_error_message( $response );
			return wp_remote_retrieve_body( $response );
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
		return apply_filters( 'dibs_easy_activate_order_args', $request_args );
	}

	/**
	 * Gets the request request body.
	 *
	 * @return array
	 */
	public function request_body() {
		$order = wc_get_order( $this->order_id );
		return array(
			'amount'     => intval( round( $order->get_total() * 100 ) ),
			'orderItems' => DIBS_Requests_Get_Order_Items::get_items( $this->order_id ),
		);
	}
}
