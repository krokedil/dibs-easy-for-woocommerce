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
class Nets_Easy_Request_Create_Order extends Nets_Easy_Request_Post {

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
		$checkout_flow                 = $this->arguments['checkout_flow'] ?? 'embedded';
		$order_id                      = $this->arguments['order_id'] ?? null;
		$payment_methods_configuration = $this->arguments['payment_methods_configuration'] ?? '';
		$invoice_fee_id                = $this->settings['dibs_invoice_fee'] ?? '';
		$merchant_number               = $this->settings['merchant_number'] ?? '';
		$request_args                  = array(
			'order'         => Nets_Easy_Order_Helper::get_order( $checkout_flow, $order_id ),
			'checkout'      => Nets_Easy_Checkout_Helper::get_checkout( $checkout_flow, $order_id ),
			'notifications' => Nets_Easy_Notification_Helper::get_notifications(),
		);

		if ( $invoice_fee_id ) {
			$request_args['paymentMethods'] = Nets_Easy_Payment_Method_Helper::get_invoice_fees();
		}

		if ( $merchant_number ) {
			$request_args['merchantNumber'] = $merchant_number;
		}

		if ( $payment_methods_configuration ) {
			$request_args['paymentMethodsConfiguration'][] = array(
				'name'    => $payment_methods_configuration,
				'enabled' => true,
			);
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
