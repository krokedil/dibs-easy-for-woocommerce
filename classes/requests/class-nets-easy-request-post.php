<?php
/**
 * Base class for all POST requests.
 *
 * @package Dibs_Easy_For_WooCommerce/Classes/Request
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *  The main class for POST requests.
 */
abstract class Nets_Easy_Request_Post extends Nets_Easy_Request {

	/**
	 * Dibs_Request_Post constructor.
	 *
	 * @param  array $arguments  The request arguments.
	 */
	public function __construct( $arguments = array() ) {
		parent::__construct( $arguments );
		$this->method = 'POST';
	}

	/**
	 * Build and return proper request arguments for this request type.
	 *
	 * @return array Request arguments
	 */
	protected function get_request_args() {
		$body     = wp_json_encode( apply_filters( 'nets_easy_request_args', $this->get_body() ) );
		$order_id = $this->arguments['order_id'] ?? null;
		return array(
			'headers'    => $this->get_request_headers( $order_id ),
			'user-agent' => $this->get_user_agent(),
			'method'     => $this->method,
			'timeout'    => apply_filters( 'nets_easy_set_timeout', 10 ),
			'body'       => $body,
		);
	}

	/**
	 * Builds the request args for a POST request.
	 *
	 * @return array
	 */
	abstract protected function get_body();
}
