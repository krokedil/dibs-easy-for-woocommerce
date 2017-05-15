<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Easy_Gateway extends WC_Payment_Gateway {

	public function __construct() {
		$this->id = 'dibs_easy';

		$this->method_title = __( 'DIBS Easy', 'woocommerce-dibs-easy' );

		$this->method_description = __( 'DIBS Easy Payment for checkout', 'woocommerce-dibs-easy' );

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings
		$this->init_settings();
		// Get the settings values
		$this->title = $this->get_option( 'title' );

		$this->enabled = $this->get_option( 'enabled' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		$this->supports = array(
			'products',
			'refunds',
		);
		// Add class if DIBS Easy is set as the gateway in session
		if ( is_checkout() ) {
			$selected_gateway = WC()->session->chosen_payment_method;
			if ( 'dibs_easy' == $selected_gateway ) {
				add_filter( 'body_class', array( $this, 'dibs_add_body_class' ) );
			}
		}
	}
	public function init_form_fields() {
		$this->form_fields = include( DIR_NAME . '/includes/dibs-settings.php' );
	}
	public function process_payment( $order_id, $retry = false ) {
		$order = wc_get_order( $order_id );

		WC()->cart->empty_cart();

		$order->payment_complete();

		WC()->session->__unset( 'dibs_incomplete_order' );
		WC()->session->__unset( 'order_awaiting_payment' );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		//Check if amount equals total order
		$order = wc_get_order( $order_id );
		if ( $amount == $order->get_total() ) {
			//Get the order information
			$cart = new DIBS_Get_WC_Cart();
			$body = $cart->get_order_cart( $order_id );
		} else {
			$body = array(
				'amount' => intval($amount),
				'orderItems' => array(
					'reference'         => 'Refund',
					'name'              => 'Refund',
					'quantity'          => 1,
					'unit'              => '1',
					'unitPrice'         => intval( $amount ),
					'taxRate'           => 0,
					'taxAmount'         => 0,
					'grossTotalAmount'  => intval( $amount ),
					'netTotalAmount'    => intval( $amount ),
				),
			);
		}

		//Get paymentID from order meta and set endpoint
		$charge_id = get_post_meta( $order_id, '_dibs_charge_id' )[0];

		// Add the sufix to the endpoint
		$endpoint_sufix = 'charges/' . $charge_id . '/refunds';

		// Make the request
		$request = new DIBS_Requests();
		$request = $request->make_request( 'POST', $body, $endpoint_sufix );
		if ( array_key_exists( 'refundId', $request ) ) { // Payment success
			$order->add_order_note( sprintf( __( 'Refund made in DIBS with charge ID %s. Reason: %s', 'woocommerce-dibs-easy' ), $request->refundId, $reason ) );
			return true;
		} else {
			return false;
		}
	}
	public function dibs_add_body_class( $class ) {
		$class[] = 'dibs-enabled';
		return $class;
	}
}// End of class DIBS_Easy_Gateway
