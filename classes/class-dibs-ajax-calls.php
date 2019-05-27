<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Ajax_Calls extends WC_AJAX {
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
			'ajax_on_checkout_error'  => true,
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

		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		$payment_id = WC()->session->get( 'dibs_payment_id' );

		// Check that the DIBS paymentId session is still valid
		if ( false === get_transient( 'dibs_payment_id_' . $payment_id ) ) {
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
			wp_send_json_success( $response );
			wp_die();
		}

	}

	/**
	 * Customer address updated - triggered when address-changed event is fired
	 */
	public static function customer_adress_updated() {

		/*
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'dibs_nonce' ) ) {
			exit( 'Nonce can not be verified.' );
		}
		*/
		$update_needed      = 'yes';
		$must_login         = 'no';
		$must_login_message = apply_filters( 'woocommerce_registration_error_email_exists', __( 'An account is already registered with your email address. Please log in.', 'woocommerce' ) );

		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		// Get customer data from Collector
		$country   = dibs_get_iso_2_country( $_REQUEST['address']['countryCode'] );
		$post_code = $_REQUEST['address']['postalCode'];

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

			// If country is changed then we need to trigger an cart update in the Collector Checkout
			if ( WC()->customer->get_billing_country() !== $country ) {
				$update_needed = 'yes';
			}

			// If country is changed then we need to trigger an cart update in the Collector Checkout
			if ( WC()->customer->get_shipping_postcode() !== $post_code ) {
				$update_needed = 'yes';
			}
			// Set customer data in Woo
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


	public static function get_order_data() {

		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		$payment_id = $_POST['paymentId'];
		// Set the endpoint sufix
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
			DIBS_Easy::log( 'Confirmation page rendered but _dibs_payment_id already exist in this order: ' . $order_id_match );
			$order    = wc_get_order( $order_id_match );
			$location = $order->get_checkout_order_received_url();
			DIBS_Easy::log( '$location: ' . $location );
			wp_send_json_error( array( 'redirect' => $location ) );
			wp_die();
		}

		// Make the request
		$request  = new DIBS_Requests_Get_DIBS_Order( $payment_id );
		$response = $request->request();

		if ( is_wp_error( $response ) || empty( $response ) ) {
			// Something went wrong
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = 'Empty response from DIBS.';
			}

			DIBS_Easy::log( 'Confirmation page rendered for DIBS payment ID ' . $payment_id . ', but something went wrong. WooCommerce form not submitted. Error message: ' . var_export( $message, true ) );

			// @todo - log and/or improve this error response?
			wp_send_json_error( $message );
			wp_die();
		} else {
			// All good with the request
			// Convert country code from 3 to 2 letters
			if ( $response->payment->consumer->shippingAddress->country ) {
				$response->payment->consumer->shippingAddress->country = dibs_get_iso_2_country( $response->payment->consumer->shippingAddress->country );
			}

			// Store the order data in a sesstion. We might need it if form processing in Woo fails
			WC()->session->set( 'dibs_order_data', $response );

			DIBS_Easy::log( 'Confirmation page rendered and checkout form about to be submitted for DIBS payment ID ' . $payment_id );

			self::prepare_cart_before_form_processing( $response->payment->consumer->shippingAddress->country );
			wp_send_json_success( $response );
			wp_die();
		}

	}

	// Change payment method
	public static function change_payment_method() {
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_fees();
		WC()->cart->calculate_totals();

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		if ( 'false' === $_POST['dibs_easy'] ) {
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

	// Helper function to prepare the cart session before processing the order form
	public static function prepare_cart_before_form_processing( $country = false ) {
		if ( $country ) {
			WC()->customer->set_billing_country( $country );
			WC()->customer->set_shipping_country( $country );
			WC()->customer->save();
			WC()->cart->calculate_totals();
		}
	}

	/**
	 * Handles WooCommerce checkout error (if checkout submission fails), after DIBS order has already been created.
	 */
	public static function ajax_on_checkout_error() {

		$create_order = new DIBS_Create_Local_Order_Fallback();
		// Create the order.
		$order    = $create_order->create_order();
		$order_id = $order->get_id();

		// Add items to order.
		$create_order->add_items_to_local_order( $order );
		// Add fees to order.
		$create_order->add_order_fees( $order );
		// Add shipping to order.
		$create_order->add_order_shipping( $order );
		// Add tax rows to order.
		$create_order->add_order_tax_rows( $order );
		// Add coupons to order.
		$create_order->add_order_coupons( $order );
		// Add customer to order.
		$create_order->add_customer_data_to_local_order( $order );
		// Add payment method
		$create_order->add_order_payment_method( $order );

		// Make sure to run Sequential Order numbers if plugin exsists
		// @Todo - Se i we can run action woocommerce_checkout_update_order_meta in this process
		// so Sequential order numbers and other plugins can do their stuff themselves
		if ( class_exists( 'WC_Seq_Order_Number_Pro' ) ) {
			$sequential = new WC_Seq_Order_Number_Pro();
			$sequential->set_sequential_order_number( $order_id );
		} elseif ( class_exists( 'WC_Seq_Order_Number' ) ) {
			$sequential = new WC_Seq_Order_Number();
			$sequential->set_sequential_order_number( $order_id, get_post( $order_id ) );
		}

		// Calculate order totals
		$create_order->calculate_order_totals( $order );

		// Update the DIBS Order with the Order ID
		$create_order->update_order_reference_in_dibs( $order->get_order_number() );

		// Add order note
		if ( ! empty( $_POST['error_message'] ) ) { // Input var okay.
			$error_message = 'Error message: ' . sanitize_text_field( trim( $_POST['error_message'] ) );
		} else {
			$error_message = 'Error message could not be retreived';
		}
		$note = sprintf( __( 'This order was made as a fallback due to an error in the checkout (%s). Please verify the order with DIBS.', 'dibs-easy-for-woocommerce' ), $error_message );
		$order->add_order_note( $note );

		$redirect_url = wc_get_endpoint_url( 'order-received', '', wc_get_page_permalink( 'checkout' ) );
		$redirect_url = add_query_arg(
			array(
				'dibs-osf' => 'true',
				'order-id' => $order_id,
			),
			$redirect_url
		);

		wp_send_json_success( array( 'redirect' => $redirect_url ) );
		wp_die();
	}

}

DIBS_Ajax_Calls::init();
