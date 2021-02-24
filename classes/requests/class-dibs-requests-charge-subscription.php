<?php
/**
 * Charge subscription renewal order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Charge subscription renewal order request class
 */
class DIBS_Request_Charge_Subscription extends DIBS_Requests2 {

	/**
	 * Class constructor.
	 *
	 * @param string $order_id WC order id.
	 * @param string $recurring_token Nets recurring token.
	 */
	public function __construct( $order_id, $recurring_token ) {
		parent::__construct();
		$this->order_id        = $order_id;
		$this->recurring_token = $recurring_token;
	}

	/**
	 * Makes the request.
	 *
	 * @return mixed
	 */
	public function request() {

		$request_url  = $this->endpoint . 'subscriptions/' . $this->recurring_token . '/charges';
		$request_args = $this->get_request_args();
		$response     = wp_remote_request( $request_url, $request_args );
		$code         = wp_remote_retrieve_response_code( $response );

		// Log the request.
		$log = Nets_Easy()->logger->format_log( $this->recurring_token, 'POST', 'Nets charge subscription ', $request_args, $request_url, json_decode( wp_remote_retrieve_body( $response ), true ), $code );
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
			'headers'    => $this->request_headers(),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'POST',
			'body'       => wp_json_encode( $this->request_body() ),
			'timeout'    => apply_filters( 'nets_easy_set_timeout', 10 ),
		);
		return apply_filters( 'dibs_easy_charge_subscription_args', $request_args );
	}

	/**
	 * Gets the request request body.
	 *
	 * @return array
	 */
	public function request_body() {
		$order                      = wc_get_order( $this->order_id );
		$body                       = array();
		$body['order']['items']     = DIBS_Requests_Get_Order_Items::get_items( $this->order_id );
		$body['order']['amount']    = intval( round( $order->get_total() * 100 ) );
		$body['order']['currency']  = $order->get_currency();
		$body['order']['reference'] = $order->get_order_number();

		return $body;
	}
}
