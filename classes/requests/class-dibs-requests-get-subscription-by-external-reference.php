<?php
/**
 * Get Subscription by External ID request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Subscription by External ID request class
 */
class DIBS_Request_Get_Subscription_By_External_Reference extends DIBS_Requests2 {

	/**
	 * $external_reference.
	 *
	 * @var string
	 */
	public $external_reference;

	/**
	 * $order_id.
	 *
	 * @var string
	 */
	public $order_id;

	/**
	 * Class constructor.
	 *
	 * @param string $external_reference The subscription recurring token used for a DIBS/Nets D2 subscription.
	 * @param string $order_id WC order id.
	 */
	public function __construct( $external_reference, $order_id ) {
		$this->external_reference = $external_reference;
		$this->order_id           = $order_id;
		parent::__construct();
	}

	/**
	 * Makes the request.
	 *
	 * @return mixed
	 */
	public function request() {
		$request_url  = $this->endpoint . 'subscriptions?externalreference=' . $this->external_reference;
		$request_args = $this->get_request_args();
		Nets_Easy()->logger->log( 'DIBS Get Subscription by externalreference args (' . $request_url . '): ' . stripslashes_deep( wp_json_encode( $request_args ) ) );

		$response = wp_remote_request( $request_url, $this->get_request_args() );
		if ( is_wp_error( $response ) ) {
			return $this->get_error_message( $response );
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			Nets_Easy()->logger->log( 'DIBS Get Subscription by externalreference response: ' . stripslashes_deep( wp_json_encode( $response ) ) );
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
