<?php
/**
 * Refund order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Krokedil\Nexi\Requests\IdempotencyKey;

/**
 * Post Nets refund order request class
 */
class Nets_Easy_Request_Refund_Order extends Nets_Easy_Request_Post {

	/**
	 * $order_id. WooCommerce order ID.
	 *
	 * @var string
	 */
	public $order_id;

	/**
	 * $charge_id. Nexi Checkout charge ID for the order.
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
		$this->log_title = 'Refund order';
		$this->order_id  = $arguments['order_id'];
	}

	/**
	 * Gets the request request body.
	 *
	 * @return array
	 */
	public function request_body() {
		return array(
			'amount'     => Nets_Easy_Refund_Helper::get_total_refund_amount( $this->order_id ),
			'orderItems' => Nets_Easy_Refund_Helper::get_refund_data( $this->order_id ),
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
			'amount'     => Nets_Easy_Refund_Helper::get_total_refund_amount( $this->order_id ),
			'orderItems' => Nets_Easy_Refund_Helper::get_refund_data( $this->order_id ),
		);
	}

	/**
	 * Get the idempotency key, so a retry after a lost response cannot refund twice.
	 *
	 * @return IdempotencyKey
	 */
	protected function get_idempotency_key() {
		return new IdempotencyKey( wc_get_order( $this->order_id ), 'refund', $this->get_request_url(), Nets_Easy_Refund_Helper::get_total_refund_amount( absint( $this->order_id ) ), true );
	}

	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		$charge_id = wc_get_order( $this->order_id )->get_meta( '_dibs_charge_id' );
		return $this->endpoint . 'charges/' . $charge_id . '/refunds';
	}
}
