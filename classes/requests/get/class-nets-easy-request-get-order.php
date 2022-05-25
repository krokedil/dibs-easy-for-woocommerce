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
class Nets_Easy_Request_Get_Order extends Nets_Easy_Request_Get {

	/**
	 * Dibs Easy payment_id.
	 *
	 * @var array
	 */
	public $payment_id;


	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request args.
	 */
	public function __construct( $arguments = array() ) {
		parent::__construct( $arguments );
		$this->log_title = 'Get order ( admin )';

		$this->payment_id = $arguments['payment_id'];
	}

	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		return $this->endpoint . 'payments/' . $this->payment_id;
	}
}
