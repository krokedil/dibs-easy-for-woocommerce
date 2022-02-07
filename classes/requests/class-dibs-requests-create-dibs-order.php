<?php
/**
 * Create new Nets order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Create new Nets order request class
 */
class DIBS_Requests_Create_DIBS_Order extends Dibs_Request_Post {

	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request arguments.
	 */
	public function __construct( $arguments = array() ) {
		parent::__construct( $arguments );
		$this->log_title = 'Create order';
	}

	/**
	 * Get the body for the request.
	 *
	 * @return array
	 */
	protected function get_body() {
		$checkout_flow   = $this->arguments['checkout_flow'] ?? 'embedded';
		$order_id        = $this->arguments['order_id'] ?? null;
		$invoice_fee_id  = $this->settings['dibs_invoice_fee'] ?? '';
		$merchant_number = $this->settings['merchant_number'] ?? '';
		$request_args    = array(
			'order'         => DIBS_Requests_Order::get_order( $checkout_flow, $order_id ),
			'checkout'      => DIBS_Requests_Checkout::get_checkout( $checkout_flow, $order_id ),
			'notifications' => DIBS_Requests_Notifications::get_notifications(),
		);

		if ( $invoice_fee_id ) {
			$request_args['paymentMethods'] = DIBS_Requests_Payment_Methods::get_invoice_fees();
		}

		if ( $merchant_number ) {
			$request_args['merchantNumber'] = $merchant_number;
		}

		return apply_filters( 'dibs_easy_create_order_args', $request_args );
	}


	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		return $this->endpoint . 'payments';
	}
}
