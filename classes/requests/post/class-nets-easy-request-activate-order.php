<?php
/**
 * Activate order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Activate order request class
 */
class Nets_Easy_Request_Activate_Order extends Nets_Easy_Request_Post {

	/**
	 * Reference to the WooCommerce order_id.
	 *
	 * @var int $order_id
	 */
	public $order_id;

	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request args.
	 */
	public function __construct( $arguments ) {
		parent::__construct( $arguments );
		$this->log_title = 'Activate order';
		$this->order_id  = $this->arguments['order_id'];
	}

	/**
	 * Get the body for the request.
	 *
	 * @return array
	 */
	protected function get_body() {
		$order = wc_get_order( $this->order_id );
		return array(
			'amount'     => intval( round( $order->get_total() * 100 ) ),
			'orderItems' => Nets_Easy_Order_Items_Helper::get_items( $this->order_id ),
		);
	}

	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		$order      = wc_get_order( $this->order_id );
		$payment_id = $order->get_transaction_id();
		return $this->endpoint . 'payments/' . $payment_id . '/charges';
	}
}
