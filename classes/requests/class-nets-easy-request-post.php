<?php
/**
 * Base class for all POST requests.
 *
 * @package Dibs_Easy_For_WooCommerce/Classes/Request
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Krokedil\Nexi\Requests\IdempotencyKey;

/**
 *  The main class for POST requests.
 */
abstract class Nets_Easy_Request_Post extends Nets_Easy_Request {

	/**
	 * The idempotency key for this request, if the endpoint takes one.
	 *
	 * @var IdempotencyKey|null
	 */
	protected $idempotency_key;

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
		$headers  = $this->get_request_headers( $order_id );

		$this->idempotency_key = $this->get_idempotency_key();
		if ( null !== $this->idempotency_key ) {
			$headers['Idempotency-Key'] = $this->idempotency_key->get_value();
		}

		return array(
			'headers'    => $headers,
			'user-agent' => $this->get_user_agent(),
			'method'     => $this->method,
			'timeout'    => apply_filters( 'nets_easy_set_timeout', 10 ),
			'body'       => $body,
		);
	}

	/**
	 * Make the request, then move to a new idempotency key if Nexi gave a definite answer.
	 *
	 * @return array|WP_Error
	 */
	public function request() {
		$response = parent::request();

		if ( null !== $this->idempotency_key ) {
			$this->idempotency_key->settle( $response );
		}

		return $response;
	}

	/**
	 * The idempotency key for a charge or refund. Requests that do not move money return null.
	 *
	 * @return IdempotencyKey|null
	 */
	protected function get_idempotency_key() {
		return null;
	}

	/**
	 * Builds the request args for a POST request.
	 *
	 * @return array
	 */
	abstract protected function get_body();
}
