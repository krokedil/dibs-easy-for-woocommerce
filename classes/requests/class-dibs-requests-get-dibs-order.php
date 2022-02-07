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
class DIBS_Requests_Get_DIBS_Order extends Dibs_Request_Get {

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
		// $this->order_id   = $arguments['order_id'] ?? null;
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
