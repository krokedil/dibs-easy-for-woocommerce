<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Easy_Gateway extends WC_Payment_Gateway {

	public $checkout_fields;

	public function __construct() {
		$this->id = 'dibs_easy';

		$this->method_title = __( 'DIBS Easy', 'dibs-easy-for-woocommerce' );

		$this->method_description = __( 'DIBS Easy Payment for checkout', 'dibs-easy-for-woocommerce' );

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings
		$this->init_settings();
		// Get the settings values
		$this->title   = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		$this->supports = array(
			'products',
			'refunds',
		);
		if ( is_checkout() ) {
			// Check if paymentId is set, check if order is ok.
			if ( isset( $_GET['paymentId'] ) ) {
				add_action( 'woocommerce_before_checkout_form', array( $this, 'dibs_get_field_values' ) );
			}
		}

		// Add class if DIBS Easy is set as the default gateway
		add_filter( 'body_class', array( $this, 'dibs_add_body_class' ) );
		add_action( 'woocommerce_thankyou_dibs_easy', array( $this, 'dibs_thankyou' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'maybe_delete_dibs_sessions' ), 100, 1 );
	}

	/**
	 * Checks if method should be available.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		if ( is_checkout() ) {
			// If we can't retrieve a set of credentials, disable KCO.
			if ( ! in_array( get_woocommerce_currency(), array( 'DKK', 'NOK', 'SEK' ) ) ) {
				return false;
			}
		}

		return true;
	}


	public function init_form_fields() {
		$this->form_fields = include DIR_NAME . '/includes/dibs-settings.php';
	}
	public function process_payment( $order_id, $retry = false ) {
		$order = wc_get_order( $order_id );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		// Check if amount equals total order
		$order = wc_get_order( $order_id );
		if ( $amount == $order->get_total() ) {
			$request = new DIBS_Request_Refund_Order( $order_id );
			$request = json_decode( $request->request() );

			if ( array_key_exists( 'refundId', $request ) ) { // Payment success
				$order->add_order_note( sprintf( __( 'Refund made in DIBS with charge ID %1$s. Reason: %2$s', 'dibs-easy-for-woocommerce' ), $request->refundId, $reason ) );
				return true;
			} else {
				return false;
			}
		} else {
			/*
			$body = array(
				'amount' => intval( $amount ),
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
			*/
			$order->add_order_note( sprintf( __( 'DIBS Easy currently only supports full refunds, for a partial refund use the DIBS backend system', 'dibs-easy-for-woocommerce' ) ) );
			return false;
		}
	}
	public function dibs_add_body_class( $class ) {
		if ( is_checkout() ) {
			$available_payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
			reset( $available_payment_gateways );
			$first_gateway = key( $available_payment_gateways );

			if ( 'dibs_easy' == $first_gateway ) {
				$class[] = 'dibs-selected';
			}
		}
		return $class;
	}
	public function dibs_thankyou( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order->has_status( array( 'processing', 'completed' ) ) ) {
			$payment_id = get_post_meta( $order_id, '_dibs_payment_id', true );
			
			$request = new DIBS_Requests_Update_DIBS_Order_Reference( $payment_id, $order_id );
			$request = $request->request();

			$request = new DIBS_Requests_Get_DIBS_Order( $payment_id );
			$request = $request->request();
			if ( key_exists( 'reservedAmount', $request->payment->summary ) ) {
				$order->update_status( 'pending' );
				update_post_meta( $order_id, 'dibs_payment_type', $request->payment->paymentDetails->paymentType );
				
				if('CARD' == $request->payment->paymentDetails->paymentType ) {
					update_post_meta( $order_id, 'dibs_customer_card', $request->payment->paymentDetails->cardDetails->maskedPan );
				}
				
				$order->add_order_note( sprintf( __( 'Order made in DIBS with Payment ID %1$s. Payment type - %2$s.', 'dibs-easy-for-woocommerce' ), $payment_id, $request->payment->paymentDetails->paymentType ) );
				$order->payment_complete( $payment_id );
				WC()->cart->empty_cart();
			}

			wc_dibs_unset_sessions();
		}
	}


	public function maybe_delete_dibs_sessions( $order_id ) {
		wc_dibs_unset_sessions();
	}


	public function dibs_get_field_values() {

		// Get the payment ID
		$payment_id = $_GET['paymentId'];

		$request               = new DIBS_Requests_Get_DIBS_Order( $payment_id );
		$this->checkout_fields = $request->request();

		//$order_id = WC()->session->get( 'dibs_incomplete_order' );

		// Check payment status
		if ( key_exists( 'reservedAmount', $this->checkout_fields->payment->summary ) ) {
			// Payment is ok, DIBS have reserved an amount
			// Convert country code from 3 to 2 letters
			if ( $this->checkout_fields->payment->consumer->shippingAddress->country ) {
				$this->checkout_fields->payment->consumer->shippingAddress->country = dibs_get_iso_2_country( $this->checkout_fields->payment->consumer->shippingAddress->country );
			}

			// Store the order data in a session. We might need it if form processing in Woo fails
			WC()->session->set( 'dibs_order_data', $this->checkout_fields );

			$this->prepare_cart_before_form_processing( $this->checkout_fields->payment->consumer->shippingAddress->country );
		} else {
			// Payment is not ok (no reservedAmount). Possibly a card without enough funds or a canceled order from 3DSecure window.
			// Redirect the customer to checkout page but change the param paymentId to dibs-payment-id.
			// By doing this the WC form will not be submitted, instead the Easy iframe will be displayed again.
			// @todo - log this event in DIBS log

			$redirect_url = add_query_arg( 'dibs-payment-id', $payment_id, trailingslashit( wc_get_checkout_url() ) );
			wp_redirect( $redirect_url );
			exit;
		}
	}

	// Helper function to prepare the cart session before processing the order form
	public function prepare_cart_before_form_processing( $country = false ) {
		if ( $country ) {
			WC()->customer->set_billing_country( $country );
			WC()->customer->set_shipping_country( $country );
			WC()->customer->save();
			WC()->cart->calculate_totals();
		}
	}

}//end class
