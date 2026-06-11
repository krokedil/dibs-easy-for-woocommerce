<?php
/**
 * Update Express order request class.
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Updates items/amount on a Nexi Express payment.
 */
class Nets_Easy_Request_Update_Express_Order extends Nets_Easy_Request_Put {

	/**
	 * The Nexi payment ID.
	 *
	 * @var string
	 */
	public $payment_id;

	/**
	 * Pre-built body supplied from the caller.
	 *
	 * @var array
	 */
	private $body;

	/**
	 * Constructor.
	 *
	 * @param array $arguments Must include 'payment_id' and 'body'.
	 */
	public function __construct( $arguments = array() ) {
		parent::__construct( $arguments );
		$this->log_title  = 'Update Express order';
		$this->payment_id = $arguments['payment_id'];
		$this->body       = $arguments['body'];
	}

	/**
	 * Get the pre-built body.
	 *
	 * @return array
	 */
	protected function get_body() {
		return apply_filters( 'nexi_express_update_order_args', $this->body );
	}

	/**
	 * Get the request URL.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		return $this->endpoint . 'payments/' . $this->payment_id . '/orderitems';
	}
}
