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
 * Terminate session request class
 */
class Nets_Easy_Request_Terminate_Session extends Nets_Easy_Request_Put {

	/**
	 * $payment_id. Nets Payment ID.
	 *
	 * @var string
	 */
	public $payment_id;

	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request args.
	 */
	public function __construct( $arguments = array() ) {
		parent::__construct( $arguments );
		$this->log_title  = 'Terminate session';
		$this->payment_id = $arguments['payment_id'];
	}

	/**
	 * Get the body for the request.
	 *
	 * @return array
	 */
	protected function get_body() {
		return array();
	}

	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		return $this->endpoint . 'payments/' . $this->payment_id . '/terminate';
	}
}
