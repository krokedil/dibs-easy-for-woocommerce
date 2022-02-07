<?php
/**
 * Refund order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Post Nets refund order request class
 */
class DIBS_Request_Refund_Order extends Dibs_Request_Post {

	/**
	 * $order_id. WooCommerce order ID.
	 *
	 * @var string
	 */
	public $order_id;

	/**
	 * $charge_id. Nets Easy charge ID for the order.
	 *
	 * @var string
	 */
	public $charge_id;

	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request args.
	 */
	public function __construct( $arguments ) {
		parent::__construct( $arguments );
		$this->order_id = $arguments['order_id'];
	}

	/**
	 * Gets the request request body.
	 *
	 * @return array
	 */
	public function request_body() {
		$order = wc_get_order( $this->order_id );
		return array(
			'amount'     => DIBS_Requests_Get_Refund_Data::get_total_refund_amount( $this->order_id ),
			'orderItems' => DIBS_Requests_Get_Refund_Data::get_refund_data( $this->order_id ),
		);
	}

	/**
	 * Get the body for the request.
	 *
	 * @return array
	 */
	protected function get_body() {
		$order = wc_get_order( $this->order_id );
		return array(
			'amount'     => DIBS_Requests_Get_Refund_Data::get_total_refund_amount( $this->order_id ),
			'orderItems' => DIBS_Requests_Get_Refund_Data::get_refund_data( $this->order_id ),
		);
	}

	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		$charge_id = get_post_meta( $this->order_id, '_dibs_charge_id', true );
		return $this->endpoint . 'charges/' . $charge_id . '/refunds';
	}
}
