<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Easy_Gateway extends WC_Payment_Gateway {

	public $checkout_fields;

	public function __construct() {
		$this->id = 'dibs_easy';

		$this->method_title = __( 'Nets Easy', 'dibs-easy-for-woocommerce' );

		$this->method_description = __( 'Nets Easy Payment for checkout', 'dibs-easy-for-woocommerce' );

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings
		$this->init_settings();
		// Get the settings values
		$this->title         = $this->get_option( 'title' );
		$this->enabled       = $this->get_option( 'enabled' );
		$this->checkout_flow = ( isset( $this->settings['checkout_flow'] ) ) ? $this->settings['checkout_flow'] : 'embedded';

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
			'subscription_payment_method_change_customer',
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
		$icon_html  = '<img src="' . $icon_src . '" alt="Nets - Payments made easy" style="max-width:' . $icon_width . 'px"/>';
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
			// If we can't retrieve a set of credentials, disable DIBS Easy.
			if ( ! in_array( get_woocommerce_currency(), array( 'DKK', 'NOK', 'SEK' ) ) ) {
				return false;
			}
		}

		return true;
	}


	public function init_form_fields() {
		$this->form_fields = include WC_DIBS_PATH . '/includes/dibs-settings.php';
	}

	public function process_payment( $order_id, $retry = false ) {
		$order = wc_get_order( $order_id );

		// Subscription payment method change.
		if ( isset( $_GET['change_payment_method'] ) ) {
			$request  = new DIBS_Requests_Create_DIBS_Order( 'redirect', $order_id );
			$response = json_decode( $request->request() );
			if ( array_key_exists( 'hostedPaymentPageUrl', $response ) ) {
				// All good. Redirect customer to DIBS payment page.
				$order->add_order_note( __( 'Customer redirected to Nets payment page.', 'dibs-easy-for-woocommerce' ) );
				return array(
					'result'   => 'success',
					'redirect' => add_query_arg( 'language', wc_dibs_get_locale(), $response->hostedPaymentPageUrl ),
				);
			} else {
				// Something else went wrong.
				if ( $response->errors ) {
					foreach ( $response->errors as $error ) {
						$error_message = $error[0];
					}
					if ( $this->is_json( $error_message ) ) {
						$error_message = json_decode( $error_message );
					}
				} else {
					$error_message = __( 'An error occured during communication with Nets. Please try again.', 'dibs-easy-for-woocommerce' );
				}
				wc_add_notice( sprintf( __( '%s', 'dibs-easy-for-woocommerce' ), wp_json_encode( $error_message ) ), 'error' );
				return false;
			}
		}

		// Regular purchase.
		if ( 'embedded' === $this->checkout_flow ) {
			// Save payment type, card details & run $order->payment_complete() if all looks good.
			if ( ! $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
				$this->process_dibs_payment_in_order( $order_id );

				// Add #dibseasy hash to checkout url so we can respond to DIBS that payment can proceed and be finalized in DIBS system.
				$response = array(
					'return_url' => $this->get_return_url( $order ),
					'time'       => time(),
				);
				return array(
					'result'   => 'success',
					'redirect' => '#dibseasy=' . base64_encode( wp_json_encode( $response ) ),
				);
			}
		} else {
			$request  = new DIBS_Requests_Create_DIBS_Order( $this->checkout_flow, $order_id );
			$response = json_decode( $request->request() );

			if ( array_key_exists( 'hostedPaymentPageUrl', $response ) ) {
				// All good. Redirect customer to DIBS payment page.
				$order->add_order_note( __( 'Customer redirected to Nets payment page.', 'dibs-easy-for-woocommerce' ) );
				update_post_meta( $order_id, '_dibs_payment_id', $response->paymentId );
				return array(
					'result'   => 'success',
					'redirect' => add_query_arg( 'language', wc_dibs_get_locale(), $response->hostedPaymentPageUrl ),
				);
			} else {
				// Something else went wrong.
				if ( $response->errors ) {
					foreach ( $response->errors as $error ) {
						$error_message = $error[0];
					}
					if ( $this->is_json( $error_message ) ) {
						$error_message = json_decode( $error_message );
					}
				} else {
					$error_message = __( 'An error occured during communication with Nets. Please try again.', 'dibs-easy-for-woocommerce' );
				}
				wc_add_notice( sprintf( __( '%s', 'dibs-easy-for-woocommerce' ), wp_json_encode( $error_message ) ), 'error' );
				return false;
			}
		}
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
		$order = wc_get_order( $order_id );

		$request = new DIBS_Request_Refund_Order( $order_id );
		$request = json_decode( $request->request() );

		if ( array_key_exists( 'refundId', $request ) ) { // Payment success
			$order->add_order_note( sprintf( __( 'Refund made in Nets with charge ID %1$s. Reason: %2$s', 'dibs-easy-for-woocommerce' ), $request->refundId, $reason ) );
			return true;
		} else {
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

		// Embedded or redirect checkout flow.
		if ( 'embedded' === $this->checkout_flow ) {
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
		} else {
			if ( ! $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
				$this->process_dibs_payment_in_order( $order_id );
			}
		}

	}

	public function process_dibs_payment_in_order( $order_id ) {
		$order = wc_get_order( $order_id );

		$payment_id = get_post_meta( $order_id, '_dibs_payment_id', true );

		$this->nets_save_shipping_reference_to_order( $order_id );

		// Update order number in DIBS system if this is the embedded checkout flow.
		if ( 'embedded' === $this->checkout_flow ) {
			$request = new DIBS_Requests_Update_DIBS_Order_Reference( $payment_id, $order_id );
			$request = $request->request();
		}

		$request = new DIBS_Requests_Get_DIBS_Order( $payment_id, $order_id );
		$request = $request->request();

		if ( isset( $request->payment->summary->reservedAmount ) || isset( $request->payment->summary->chargedAmount ) || isset( $request->payment->subscription->id ) ) {

			do_action( 'dibs_easy_process_payment', $order_id, $request );

			update_post_meta( $order_id, 'dibs_payment_type', $request->payment->paymentDetails->paymentType );
			update_post_meta( $order_id, 'dibs_payment_method', $request->payment->paymentDetails->paymentMethod );
			update_post_meta( $order_id, '_dibs_date_paid', date( 'Y-m-d H:i:s' ) );

			if ( 'CARD' == $request->payment->paymentDetails->paymentType ) {
				update_post_meta( $order_id, 'dibs_customer_card', $request->payment->paymentDetails->cardDetails->maskedPan );
			}

			if ( 'A2A' === $request->payment->paymentDetails->paymentType ) {
				$order->add_order_note( sprintf( __( 'Order made in Nets with Payment ID %1$s. Payment type - %2$s.', 'dibs-easy-for-woocommerce' ), $payment_id, $request->payment->paymentDetails->paymentMethod ) );
			} else {
				$order->add_order_note( sprintf( __( 'Order made in Nets with Payment ID %1$s. Payment type - %2$s.', 'dibs-easy-for-woocommerce' ), $payment_id, $request->payment->paymentDetails->paymentType ) );
			}
			$order->payment_complete( $payment_id );
		} else {
			// Purchase not finalized in DIBS.
			// If this is a redirect checkout flow let's redirect the customer to cart page.
			if ( 'embedded' !== $this->checkout_flow ) {
				wp_safe_redirect( html_entity_decode( $order->get_cancel_order_url() ) );
				exit;
			}
		}

		if ( 'embedded' === $this->checkout_flow ) {
			$this->maybe_add_invoice_fee( $order_id );
		}
	}

	/**
	 * Save shipping reference to Order.
	 *
	 * @param int $order_id order id.
	 * @return void
	 */
	public function nets_save_shipping_reference_to_order( $order_id ) {
		$packages        = WC()->shipping->get_packages();
		$chosen_methods  = WC()->session->get( 'chosen_shipping_methods' );
		$chosen_shipping = $chosen_methods[0];
		foreach ( $packages as $i => $package ) {
			foreach ( $package['rates'] as $method ) {
				if ( $chosen_shipping === $method->id ) {
					update_post_meta( $order_id, 'nets_shipping_reference', 'shipping|' . $method->id );
				}
			}
		}
	}

	public function maybe_delete_dibs_sessions( $order_id ) {
		wc_dibs_unset_sessions();
	}

	public function is_json( $string ) {
		json_decode( $string );
		return ( json_last_error() == JSON_ERROR_NONE );
	}

}//end class
