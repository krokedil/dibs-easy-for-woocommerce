<?php
/**
 * Main class for GET requests.
 *
 * @package Dibs_Easy_For_WooCommerce/Classes/Requests
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main class for GET requests.
 */
abstract class Dibs_Request_Get extends DIBS_Requests2 {

	/**
	 * $order_id.
	 *
	 * @var int|null
	 */
	public $order_id;


	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request arguments.
	 */
	public function __construct( $arguments = array() ) {
		parent::__construct( $arguments );
		$this->method   = 'GET';
		$this->order_id = $arguments['order_id'] ?? null;
	}

	/**
	 * Builds the request args for a GET request.
	 *
	 * @return array
	 */
	public function get_request_args() {
		// todo todo order id preko argumentsa , mora da se proveri.
		return array(
			'headers'    => $this->get_request_headers( $this->order_id ),
			'user-agent' => $this->get_user_agent(),
			'method'     => $this->method,
		);
	}
}