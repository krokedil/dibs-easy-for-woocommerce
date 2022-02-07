<?php
/**
 * Cancel order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Cancel order request class
 */
class DIBS_Requests_Cancel_Order extends Dibs_Request_Post {

	/**
	 * Reference to order_id.
	 *
	 * @var $order_id
	 */
	public $order_id;

	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request args.
	 */
	public function __construct( $arguments = array() ) {
		parent::__construct( $arguments );
		$this->log_title = 'Cancel order';
		$this->order_id  = $arguments['order_id'];
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
			'orderItems' => DIBS_Requests_Get_Order_Items::get_items( $this->order_id ),
		);
	}


	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		$payment_id = get_post_meta( $this->order_id, '_dibs_payment_id', true );
		return $this->endpoint . 'payments/' . $payment_id . '/cancels';
	}
}
