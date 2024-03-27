<?php
/**
 * Charge subscription renewal order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Charge subscription renewal order request class
 */
class Nets_Easy_Request_Charge_Subscription extends Nets_Easy_Request_Post {

	/**
	 * The WooCommerce order id.
	 *
	 * @var int $order_id
	 */
	public $order_id;

	/**
	 * The Nets recurring token.
	 *
	 * @var mixed
	 */
	public $recurring_token;

	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request args.
	 */
	public function __construct( $arguments ) {
		parent::__construct( $arguments );
		$this->log_title       = 'Charge subscription';
		$this->order_id        = $arguments['order_id'];
		$this->recurring_token = $arguments['recurring_token'];
	}


	/**
	 * Get the body for the request.
	 *
	 * @return array
	 */
	protected function get_body() {
		$order                      = wc_get_order( $this->order_id );
		$body                       = array();
		$body['order']['items']     = Nets_Easy_Order_Items_Helper::get_items( $this->order_id );
		$body['order']['amount']    = intval( round( $order->get_total() * 100 ) );
		$body['order']['currency']  = $order->get_currency();
		$body['order']['reference'] = $order->get_order_number();
		return $body;
	}

	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		return $this->endpoint . 'subscriptions/' . $this->recurring_token . '/charges';
	}
}
