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
			'update_checkout'              => true,
			'customer_adress_updated'      => true,
			'get_order_data'               => true,
			'dibs_add_customer_order_note' => true,
			'change_payment_method'        => true,
			'ajax_on_checkout_error'       => true,
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

		// $order_id   = WC()->session->get( 'dibs_incomplete_order' );
		$payment_id = WC()->session->get( 'dibs_payment_id' );

		// Check that the DIBS paymentId session is still valid
		if ( false === get_transient( 'dibs_payment_id_' . $payment_id ) ) {
			wc_dibs_unset_sessions();
			$return['redirect_url'] = wc_get_checkout_url();
			wp_send_json_error( $return );
			wp_die();
		}

		// $request = new DIBS_Requests();
		// $response = $request->update_dibs_order( $order_id, $payment_id );
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
		$update_needed = 'yes';

		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		// Update DIBS with Woo order number 
		/*
		$order_id = wc_dibs_get_order_id();
		$payment_id = WC()->session->get( 'dibs_payment_id' );
		$request = new DIBS_Requests_Update_DIBS_Order_Reference( $payment_id, $order_id );
		$request = $request->request();
		*/
		// Get customer data from Collector
		$country   = dibs_get_iso_2_country( $_REQUEST['address']['countryCode'] );
		$post_code = $_REQUEST['address']['postalCode'];

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
			'updateNeeded' => $update_needed,
			'country'      => $country,
			'postCode'     => $post_code,
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

		// Make the request
		// $request  = new DIBS_Requests();
		// $response = $request->make_request( 'GET', '', $endpoint_sufix );
		$request  = new DIBS_Requests_Get_DIBS_Order( $payment_id );
		$response = $request->request();
		
		//$order_id = WC()->session->get( 'dibs_incomplete_order' );
		
		
		if ( is_wp_error( $response ) || empty( $response ) ) {
			// Something went wrong
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = 'Empty response from DIBS.';
			}
			//$order = wc_get_order( $order_id );
			//$order->add_order_note( sprintf( __( 'Something went wrong when connecting to DIBS during checkout completion. Error message: %s. Please check your DIBS backoffice to control the order.', 'dibs-easy-for-woocommerce' ), $message ) );
			wp_send_json_error( $message );
			wp_die();
		} else {
			// All good with the request
			// Convert country code from 3 to 2 letters
			if ( $response->payment->consumer->shippingAddress->country ) {
				$response->payment->consumer->shippingAddress->country = dibs_get_iso_2_country( $response->payment->consumer->shippingAddress->country );
			}

			// Maybe add customer order note
			if ( null != WC()->session->get( 'dibs_customer_order_note' ) ) {
				$response->order_note = WC()->session->get( 'dibs_customer_order_note' );
			} else {
				$response->order_note = '';
			}

			// Store the order data in a sesstion. We might need it if form processing in Woo fails
			WC()->session->set( 'dibs_order_data', $response );

			self::prepare_cart_before_form_processing( $response->payment->consumer->shippingAddress->country );
			//self::prepare_local_order_before_form_processing( $order_id, $payment_id );
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

	// Helper function to prepare the local order before processing the order form
	public static function prepare_local_order_before_form_processing( $order_id, $payment_id ) {
		// Update cart hash
		$cart_hash	= md5( wp_json_encode( wc_clean( WC()->cart->get_cart_for_session() ) ) . WC()->cart->total );
		update_post_meta( $order_id, '_cart_hash', $cart_hash );
		DIBS_Easy::log('Saving DIBS _cart_hash (in prepare_local_order_before_form_processing) ' . $cart_hash . ' in order id ' . $order_id );

		// Set the paymentID as a meta value to be used later for reference
		update_post_meta( $order_id, '_dibs_payment_id', $payment_id );
		// Order ready for processing
		WC()->session->set( 'order_awaiting_payment', $order_id );
		$order = wc_get_order( $order_id );
		$order->update_status( 'pending' );
	}

	// Function called if a ajax call does not receive the expected result
	public static function fail_ajax_call( $order, $message = 'Failed to create an order with DIBS' ) {
		$order->add_order_note( sprintf( __( '%s', 'dibs-easy-for-woocommerce' ), $message ) );
		return $message;
	}

	public static function dibs_add_customer_order_note() {
		WC()->session->set( 'dibs_customer_order_note', $_POST['order_note'] );
		wp_send_json_success( $_POST['order_note'] );
		wp_die();
	}

	/**
	 * Handles WooCommerce checkout error (if checkout submission fails), after DIBS order has already been created.
	 */
	public static function ajax_on_checkout_error() {

		/*
		$order_id        = WC()->session->get( 'dibs_incomplete_order' );
		$order           = wc_get_order( $order_id );
		$dibs_order_data = WC()->session->get( 'dibs_order_data' );

		// Error message
		if ( ! empty( $_POST['error_message'] ) ) { // Input var okay.
			$error_message = 'Error message: ' . sanitize_text_field( trim( $_POST['error_message'] ) );
		} else {
			$error_message = 'Error message could not be retreived';
		}

		// Add customer data to order
		self::helper_add_customer_data_to_local_order( $order, $dibs_order_data );

		// Add payment method to order
		self::add_order_payment_method( $order );

		// Add order items
		self::helper_add_items_to_local_order( $order_id );

		// Add order fees.
		self::helper_add_order_fees( $order );

		// Add order shipping.
		self::helper_add_order_shipping( $order );

		// Add order taxes.
		self::helper_add_order_tax_rows( $order );

		// Store coupons.
		self::helper_add_order_coupons( $order );

		// Add an order note to inform merchant that the order has been finalized via a fallback routine.
		$note = sprintf( __( 'This order was made as a fallback due to an error in the checkout (%s). Please verify the order with DIBS.', 'dibs-easy-for-woocommerce' ), $error_message );
			$order->add_order_note( $note );

		// Save order totals
		$order->calculate_totals();
		$order->save();
		*/
		
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
			), $redirect_url
		);

		wp_send_json_success( array( 'redirect' => $redirect_url ) );
		wp_die();
	}

	/**
	 * Adds order items to ongoing order.
	 *
	 * @param  integer $local_order_id WooCommerce order ID.
	 * @throws Exception PHP Exception.
	 */
	public static function helper_add_items_to_local_order( $order_id ) {
		$local_order = wc_get_order( $order_id );
		// Remove items as to stop the item lines from being duplicated.
		$local_order->remove_order_items();
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) { // Store the line items to the new/resumed order.
			$item_id = $local_order->add_product(
				$values['data'], $values['quantity'], array(
					'variation' => $values['variation'],
					'totals'    => array(
						'subtotal'     => $values['line_subtotal'],
						'subtotal_tax' => $values['line_subtotal_tax'],
						'total'        => $values['line_total'],
						'tax'          => $values['line_tax'],
						'tax_data'     => $values['line_tax_data'],
					),
				)
			);
			if ( ! $item_id ) {
				throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 525 ) );
			}
			do_action( 'woocommerce_add_order_item_meta', $item_id, $values, $cart_item_key ); // Allow plugins to add order item meta.
		}
	}

	/**
	 * Adds order fees to local order.
	 *
	 * @since  1.1.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 *
	 * @throws Exception PHP Exception.
	 */
	public static function helper_add_order_fees( $order ) {
		$order_id = $order->get_id();
		foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {
			$item_id = $order->add_fee( $fee );
			if ( ! $item_id ) {
				throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
			}
			// Allow plugins to add order item meta to fees.
			do_action( 'woocommerce_add_order_fee_meta', $order_id, $item_id, $fee, $fee_key );
		}
	}

	/**
	 * Adds order shipping to local order.
	 *
	 * @since  1.1.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 *
	 * @throws Exception PHP Exception.
	 * @internal param object $klarna_order Klarna order.
	 */
	public static function helper_add_order_shipping( $order ) {
		if ( ! defined( 'WOOCOMMERCE_CART' ) ) {
			define( 'WOOCOMMERCE_CART', true );
		}
		$order_id              = $order->get_id();
		$this_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
		WC()->cart->calculate_shipping();
		// Store shipping for all packages.
		foreach ( WC()->shipping->get_packages() as $package_key => $package ) {
			if ( isset( $package['rates'][ $this_shipping_methods[ $package_key ] ] ) ) {
				$item_id = $order->add_shipping( $package['rates'][ $this_shipping_methods[ $package_key ] ] );
				if ( ! $item_id ) {
					throw new Exception( __( 'Error: Unable to add shipping item. Please try again.', 'woocommerce' ) );
				}
				// Allows plugins to add order item meta to shipping.
				do_action( 'woocommerce_add_shipping_order_item', $order_id, $item_id, $package_key );
			}
		}
	}

	/**
	 * Adds order tax rows to local order.
	 *
	 * @since  1.1.0
	 * @param  object $order Local WC order.
	 *
	 * @throws Exception PHP Exception.
	 */
	public static function helper_add_order_tax_rows( $order ) {
		// Store tax rows.
		foreach ( array_keys( WC()->cart->taxes + WC()->cart->shipping_taxes ) as $tax_rate_id ) {
			if ( $tax_rate_id && ! $order->add_tax( $tax_rate_id, WC()->cart->get_tax_amount( $tax_rate_id ), WC()->cart->get_shipping_tax_amount( $tax_rate_id ) ) && apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) !== $tax_rate_id ) {
				throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 405 ) );
			}
		}
	}

	/**
	 * Adds order coupons to local order.
	 *
	 * @since  1.1.0
	 * @access public
	 *
	 * @param  object $order Local WC order.
	 *
	 * @throws Exception PHP Exception.
	 */
	public static function helper_add_order_coupons( $order ) {
		foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
			if ( ! $order->add_coupon( $code, WC()->cart->get_coupon_discount_amount( $code ) ) ) {
				throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
			}
		}
	}

	/**
	 * Adds payment method to local order.
	 *
	 * @since  1.1.0
	 * @access public
	 */
	public static function add_order_payment_method( $order ) {
		$available_gateways = WC()->payment_gateways->payment_gateways();
		$payment_method     = $available_gateways['dibs_easy'];
		$order->set_payment_method( $payment_method );
	}

	/**
	 * Adds customer data to WooCommerce order.
	 *
	 * @since  1.1.0
	 * @param integer $order_id         WooCommerce order ID.
	 * @param array   $dibs_order_data  Customer data returned by DIBS.
	 */
	public static function helper_add_customer_data_to_local_order( $order, $dibs_order_data ) {
		$order_id   = $order->get_id();
		$first_name = (string) $dibs_order_data->payment->consumer->privatePerson->firstName;
		$last_name  = (string) $dibs_order_data->payment->consumer->privatePerson->lastName;
		$email      = (string) $dibs_order_data->payment->consumer->privatePerson->email;
		$country    = (string) $dibs_order_data->payment->consumer->shippingAddress->country;
		$address    = (string) $dibs_order_data->payment->consumer->shippingAddress->addressLine1;
		$city       = (string) $dibs_order_data->payment->consumer->shippingAddress->city;
		$postcode   = (string) $dibs_order_data->payment->consumer->shippingAddress->postalCode;
		$phone      = (string) $dibs_order_data->payment->consumer->privatePerson->phoneNumber->number;

		update_post_meta( $order_id, '_billing_first_name', $first_name );
		update_post_meta( $order_id, '_billing_last_name', $last_name );
		update_post_meta( $order_id, '_billing_address_1', $address );
		update_post_meta( $order_id, '_billing_city', $city );
		update_post_meta( $order_id, '_billing_postcode', $postcode );
		update_post_meta( $order_id, '_billing_country', $country );
		update_post_meta( $order_id, '_billing_phone', $phone );
		update_post_meta( $order_id, '_billing_email', $email );

		update_post_meta( $order_id, '_shipping_first_name', $first_name );
		update_post_meta( $order_id, '_shipping_last_name', $last_name );
		update_post_meta( $order_id, '_shipping_address_1', $address );
		update_post_meta( $order_id, '_shipping_city', $city );
		update_post_meta( $order_id, '_shipping_postcode', $postcode );
		update_post_meta( $order_id, '_shipping_country', $country );
	}


}

DIBS_Ajax_Calls::init();
