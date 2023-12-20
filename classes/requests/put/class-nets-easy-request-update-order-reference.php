<?php
/**
 * Update order reference request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Update order reference request class
 */
class Nets_Easy_Request_Update_Order_Reference extends Nets_Easy_Request_Put {

	/**
	 * $payment_id. Nets Payment ID.
	 *
	 * @var string
	 */
	public $payment_id;

	/**
	 * WooCommerce order ID.
	 *
	 * @var string
	 */
	public $order_id;

	/**
	 * Order number.
	 *
	 * @var string
	 */
	public $order_number;

	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request args.
	 */
	public function __construct( $arguments ) {
		parent::__construct( $arguments );
		$this->payment_id = $arguments['payment_id'];
		$this->order_id   = isset( $arguments['order_id'] ) ? $this->arguments['order_id'] : null;

		$this->order_number = $this->get_order_number( $arguments['order_id'] );
		$this->log_title    = 'Update order reference ';

	}

	/**
	 * Gets the order number for the order.
	 *
	 * @param string $order_id WC order id.
	 * @return string
	 */
	public function get_order_number( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( is_object( $order ) ) {
			// Make sure to run Sequential Order numbers if plugin exists.
			if ( class_exists( 'WC_Seq_Order_Number_Pro' ) ) {
				$sequential = new WC_Seq_Order_Number_Pro();
				$sequential->set_sequential_order_number( $order_id );
				$reference = $sequential->get_order_number( $order->get_order_number(), $order );
			} elseif ( class_exists( 'WC_Seq_Order_Number' ) ) {
				$sequential = new WC_Seq_Order_Number();
				$sequential->set_sequential_order_number( $order_id, wc_get_order( $order_id ) );
				$reference = $sequential->get_order_number( $order->get_order_number(), $order );
			} else {
				$reference = $order->get_order_number();
			}
		} else {
			$reference = $order_id;
		}
		return $reference;
	}

	/**
	 * Get the body for the request.
	 *
	 * @return array
	 */
	protected function get_body() {
		return array(
			'reference'   => $this->order_number,
			'checkoutUrl' => wc_get_checkout_url(),
		);
	}

	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		return $this->endpoint . 'payments/' . $this->payment_id . '/referenceinformation';
	}
}
