<?php
/**
 * Create Express order request class.
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates a Nexi payment with integrationType Express.
 */
class Nets_Easy_Request_Create_Express_Order extends Nets_Easy_Request_Post {

	/**
	 * Pre-built body supplied from the caller.
	 *
	 * @var array
	 */
	private $body;

	/**
	 * Constructor.
	 *
	 * @param array $arguments Request arguments; must include 'body'.
	 */
	public function __construct( $arguments = array() ) {
		parent::__construct( $arguments );
		$this->log_title = 'Create Express order';
		$this->body      = $arguments['body'];
	}

	/**
	 * Get the pre-built body.
	 *
	 * @return array
	 */
	protected function get_body() {
		return apply_filters( 'nexi_express_create_order_args', $this->body );
	}

	/**
	 * Get the request URL.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		return $this->endpoint . 'payments';
	}
}
