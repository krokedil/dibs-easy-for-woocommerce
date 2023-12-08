<?php
/**
 * Main class for PATCH requests.
 *
 * @package Dibs_Easy_For_WooCommerce/Classes/Requests
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main class for PATCH requests.
 */
abstract class Nets_Easy_Request_Put extends Nets_Easy_Request {

	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request arguments.
	 */
	public function __construct( $arguments ) {
		parent::__construct( $arguments );
		$this->method = 'PUT';
	}

	/**
	 * Builds the request args for a PUT request.
	 *
	 * @return array
	 */
	public function get_request_args() {
		$body     = wp_json_encode( $this->get_body() );
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
	 * Get the request body.
	 *
	 * @return array
	 */
	abstract protected function get_body();
}
