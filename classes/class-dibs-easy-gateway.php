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
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change_admin',
			'subscription_payment_method_change',
			'multiple_subscriptions',
		);

		// Add class if DIBS Easy is set as the default gateway
		add_filter( 'body_class', array( $this, 'dibs_add_body_class' ) );
		add_action( 'woocommerce_thankyou_dibs_easy', array( $this, 'dibs_thankyou' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'maybe_delete_dibs_sessions' ), 100, 1 );
	}

	/**
	 * Get gateway icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icon_src   = 'https://cdn.dibspayment.com/logo/checkout/combo/horiz/DIBS_checkout_kombo_horizontal_04.png';
		$icon_width = '145';
		$icon_html  = '<img src="' . $icon_src . '" alt="DIBS - Payments made easy" style="max-width:' . $icon_width . 'px"/>';
		return apply_filters( 'wc_dibs_easy_icon_html', $icon_html );
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

		// Save payment type, card details & run $order->payment_complete() if all looks good.
		if ( ! $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
			$this->process_dibs_payment_in_order( $order_id );
		}

		// Redirect customer to thank you page
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}


	public function maybe_add_invoice_fee( $order_id ) {
		// Add invoice fee to order
		$order = wc_get_order( $order_id );
		if ( 'INVOICE' == get_post_meta( $order_id, 'dibs_payment_type', true ) ) {
			$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
			if ( isset( $dibs_settings['dibs_invoice_fee'] ) && ! empty( $dibs_settings['dibs_invoice_fee'] ) ) {
				$invoice_fee_id = $dibs_settings['dibs_invoice_fee'];
				$invoice_fee    = wc_get_product( $invoice_fee_id );

				if ( is_object( $invoice_fee ) ) {
					$fee      = new WC_Order_Item_Fee();
					$fee_args = array(
						'name'  => $invoice_fee->get_name(),
						'total' => wc_get_price_excluding_tax( $invoice_fee ),
					);

					$fee->set_props( $fee_args );
					if ( 'none' == $invoice_fee->get_tax_status() ) {
						$tax_amount = '0';
						$fee->set_total_tax( $tax_amount );
						$fee->set_tax_status( $invoice_fee->get_tax_status() );
					} else {
						$fee->set_tax_class( $invoice_fee->get_tax_class() );
					}

					$order->add_item( $fee );
					$order->calculate_totals();
					$order->save();
				}
			}
		}
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

		// Save payment type, card details & run $order->payment_complete() if all looks good.
		if ( ! $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
			$this->process_dibs_payment_in_order( $order_id );
			$order->add_order_note( __( 'Order finalized in thankyou page.', 'dibs-easy-for-woocommerce' ) );
			WC()->cart->empty_cart();
		}

		// Clear sessionStorage.
		echo '<script>sessionStorage.removeItem("DIBSRequiredFields")</script>';
		echo '<script>sessionStorage.removeItem("DIBSFieldData")</script>';

		// Unset sessions.
		wc_dibs_unset_sessions();
	}


	public function process_dibs_payment_in_order( $order_id ) {
		$order = wc_get_order( $order_id );

		$payment_id = get_post_meta( $order_id, '_dibs_payment_id', true );
		$request    = new DIBS_Requests_Update_DIBS_Order_Reference( $payment_id, $order_id );
		$request    = $request->request();

		$request = new DIBS_Requests_Get_DIBS_Order( $payment_id );
		$request = $request->request();

		if ( isset( $request->payment->summary->reservedAmount ) || $request->payment->summary->chargedAmount || isset( $request->payment->subscription->id ) ) {

			do_action( 'dibs_easy_process_payment', $order_id, $request );

			update_post_meta( $order_id, 'dibs_payment_type', $request->payment->paymentDetails->paymentType );
			update_post_meta( $order_id, 'dibs_payment_method', $request->payment->paymentDetails->paymentMethod );

			if ( 'CARD' == $request->payment->paymentDetails->paymentType ) {
				update_post_meta( $order_id, 'dibs_customer_card', $request->payment->paymentDetails->cardDetails->maskedPan );
			}

			if ( 'A2A' === $request->payment->paymentDetails->paymentType ) {
				$order->add_order_note( sprintf( __( 'Order made in DIBS with Payment ID %1$s. Payment type - %2$s.', 'dibs-easy-for-woocommerce' ), $payment_id, $request->payment->paymentDetails->paymentMethod ) );
			} else {
				$order->add_order_note( sprintf( __( 'Order made in DIBS with Payment ID %1$s. Payment type - %2$s.', 'dibs-easy-for-woocommerce' ), $payment_id, $request->payment->paymentDetails->paymentType ) );
			}
			$order->payment_complete( $payment_id );
		}
		$this->maybe_add_invoice_fee( $order_id );
	}


	public function maybe_delete_dibs_sessions( $order_id ) {
		wc_dibs_unset_sessions();
	}

	/**
	 * Helper function to prepare the cart session before processing the order form
	 *
	 * @param string|boolean $country Country returned from DIBS.
	 * @return void
	 */
	public function prepare_cart_before_form_processing( $country = false ) {
		if ( $country ) {
			WC()->customer->set_billing_country( $country );
			WC()->customer->set_shipping_country( $country );
			WC()->customer->save();
			WC()->cart->calculate_totals();
		}
	}

}//end class
