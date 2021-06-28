<?php
/**
 * Ajax class
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Ajax class.
 */
class DIBS_Ajax_Calls extends WC_AJAX {

	/**
	 * $private_key. Nets private key.
	 *
	 * @var string
	 */
	public $private_key;

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {
		$ajax_events = array(
			'update_checkout'         => true,
			'customer_adress_updated' => true,
			'get_order_data'          => true,
			'change_payment_method'   => true,
		);
		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );
				// WC AJAX can be used for frontend ajax requests.
				add_action( 'wc_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	/**
	 * Update DIBS Easy Checkout - executed when Woo updated_checkout event has been triggered
	 */
	public static function update_checkout() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'nets_checkout' ) ) {
			wp_send_json_error( 'bad_nonce' );
			exit;
		}

		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		$payment_id = WC()->session->get( 'dibs_payment_id' );

		// Check that the DIBS paymentId session is still valid.
		if ( false === get_transient( 'dibs_payment_id_' . $payment_id ) || WC()->session->get( 'dibs_currency' ) !== get_woocommerce_currency() ) {
			wc_dibs_unset_sessions();
			$return['redirect_url'] = wc_get_checkout_url();
			wp_send_json_error( $return );
			wp_die();
		}

		$request  = new DIBS_Requests_Update_DIBS_Order( $payment_id );
		$response = $request->request();
		if ( is_wp_error( $response ) ) {
			wc_dibs_unset_sessions();
			$return['redirect_url'] = wc_get_checkout_url();
			wp_send_json_error( $return );
			wp_die();
		} else {
			wp_send_json_success(
				array(
					'dibs_response' => $response,
					'nonce'         => wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce', true, false ),
				)
			);
			wp_die();
		}

	}

	/**
	 * Customer address updated - triggered when address-changed event is fired
	 */
	public static function customer_adress_updated() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'nets_checkout' ) ) {
			wp_send_json_error( 'bad_nonce' );
			exit;
		}

		$update_needed      = 'no';
		$must_login         = 'no';
		$must_login_message = apply_filters( 'woocommerce_registration_error_email_exists', __( 'An account is already registered with your email address. Please log in.', 'woocommerce' ) );

		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		// Get customer data from Nets.
		$address   = filter_input( INPUT_POST, 'address', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$country   = dibs_get_iso_2_country( $address['countryCode'] );
		$post_code = $address['postalCode'];

		// If customer is not logged in and this is a subscription purchase - get customer email from DIBS.
		if ( ! is_user_logged_in() && ( ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) || 'no' === get_option( 'woocommerce_enable_guest_checkout' ) ) ) {
			$payment_id = WC()->session->get( 'dibs_payment_id' );
			$request    = new DIBS_Requests_Get_DIBS_Order( $payment_id );
			$response   = $request->request();
			$email      = $response->payment->consumer->privatePerson->email;
			if ( email_exists( $email ) ) {
				// Email exist in a user account, customer must login.
				$must_login = 'yes';
			}
		}

		if ( $country ) {
			// If country is changed then we need to trigger an cart update in the DIBS Easy Checkout.
			if ( WC()->customer->get_billing_country() !== $country ) {
				$update_needed = 'yes';
			}

			// If country is changed then we need to trigger an cart update in the DIBS Easy Checkout.
			if ( WC()->customer->get_shipping_postcode() !== $post_code ) {
				$update_needed = 'yes';
			}
			// Set customer data in Woo.
			WC()->customer->set_billing_country( $country );
			WC()->customer->set_shipping_country( $country );
			WC()->customer->set_billing_postcode( $post_code );
			WC()->customer->set_shipping_postcode( $post_code );
			WC()->customer->save();

			WC()->cart->calculate_totals();

		}
		$response = array(
			'updateNeeded'     => $update_needed,
			'country'          => $country,
			'postCode'         => $post_code,
			'mustLogin'        => $must_login,
			'mustLoginMessage' => $must_login_message,
		);
		wp_send_json_success( $response );
		wp_die();
	}

	/**
	 * Get Nets order data, right before WC form is submitted in checkout.
	 */
	public static function get_order_data() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'nets_checkout' ) ) {
			wp_send_json_error( 'bad_nonce' );
			exit;
		}

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		$payment_id = filter_input( INPUT_POST, 'paymentId', FILTER_SANITIZE_STRING );
		// Set the endpoint sufix.
		$endpoint_sufix = 'payments/' . $payment_id;

		// Prevent duplicate orders if payment complete event is triggered twice or if order already exist in Woo (via webhook).
		$query          = new WC_Order_Query(
			array(
				'limit'          => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'return'         => 'ids',
				'payment_method' => 'dibs_easy',
				'date_created'   => '>' . ( time() - DAY_IN_SECONDS ),
			)
		);
		$orders         = $query->get_orders();
		$order_id_match = null;
		foreach ( $orders as $order_id ) {
			$order_payment_id = get_post_meta( $order_id, '_dibs_payment_id', true );
			if ( strtolower( $order_payment_id ) === strtolower( $payment_id ) ) {
				$order_id_match = $order_id;
				break;
			}
		}
		// _dibs_payment_id already exist in an order. Let's redirect the customer to the thankyou page for that order.
		if ( $order_id_match ) {
			$order = wc_get_order( $order_id_match );
			if ( $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
				Nets_Easy()->logger->log( 'Process Woo checkout triggered but _dibs_payment_id already exist in this order: ' . $order_id_match );
				$location = $order->get_checkout_order_received_url();
				Nets_Easy()->logger->log( '$location: ' . $location );
				wp_send_json_error( array( 'redirect' => $location ) );
				wp_die();
			}
		}

		// Make the request.
		$request  = new DIBS_Requests_Get_DIBS_Order( $payment_id );
		$response = $request->request();

		if ( is_wp_error( $response ) || empty( $response ) ) {
			// Something went wrong.
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = 'Empty response from Nets.';
			}

			Nets_Easy()->logger->log( 'processWooCheckout triggered for Nets payment ID ' . $payment_id . ', but something went wrong. WooCommerce form not submitted. Error message: ' . wp_json_encode( $message ) );

			// @todo - log and/or improve this error response?
			wp_send_json_error( $message );
			wp_die();
		} else {
			// All good with the request.
			// Convert country code from 3 to 2 letters.
			if ( $response->payment->consumer->shippingAddress->country ) {
				$response->payment->consumer->shippingAddress->country = dibs_get_iso_2_country( $response->payment->consumer->shippingAddress->country );
			}

			// Store the order data in a sesstion. We might need it if form processing in Woo fails.
			WC()->session->set( 'dibs_order_data', $response );

			Nets_Easy()->logger->log( 'processWooCheckout triggered and checkout form about to be submitted for Nets payment ID ' . $payment_id );

			self::prepare_cart_before_form_processing( $response->payment->consumer->shippingAddress->country );
			wp_send_json_success( $response );
			wp_die();
		}

	}

	/**
	 * Change payment method.
	 */
	public static function change_payment_method() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( $_POST['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'nets_checkout' ) ) {
			wp_send_json_error( 'bad_nonce' );
			exit;
		}

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$dibs_easy          = filter_input( INPUT_POST, 'dibs_easy', FILTER_SANITIZE_STRING );
		if ( 'false' === $dibs_easy ) {
			// Set chosen payment method to first gateway that is not DIBS Easy.
			$first_gateway = reset( $available_gateways );
			if ( 'dibs_easy' !== $first_gateway->id ) {
				WC()->session->set( 'chosen_payment_method', $first_gateway->id );
			} else {
				$second_gateway = next( $available_gateways );
				WC()->session->set( 'chosen_payment_method', $second_gateway->id );
			}
		} else {
			WC()->session->set( 'chosen_payment_method', 'dibs_easy' );
		}
		WC()->payment_gateways()->set_current_gateway( $available_gateways );

		$redirect = wc_get_checkout_url();
		$data     = array(
			'redirect' => $redirect,
		);
		wp_send_json_success( $data );
		wp_die();
	}

	/**
	 * Helper function to prepare the cart session before processing the order form.
	 *
	 * @param string $country Customer country.
	 */
	public static function prepare_cart_before_form_processing( $country = false ) {
		if ( $country ) {
			WC()->customer->set_billing_country( $country );
			WC()->customer->set_shipping_country( $country );
			WC()->customer->save();
			WC()->cart->calculate_totals();
		}
	}

}

DIBS_Ajax_Calls::init();
