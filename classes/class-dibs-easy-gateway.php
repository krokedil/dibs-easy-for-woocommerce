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
		$this->title = $this->get_option( 'title' );
		$this->enabled = $this->get_option( 'enabled' );
		
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		$this->supports = array(
			'products',
			'refunds',
		);
		if ( is_checkout() ) {
			// Check if paymentId is set, if it is then populate the fields
			if ( isset( $_GET['paymentId'] ) ) {
				add_action( 'woocommerce_before_checkout_form', array( $this, 'dibs_get_field_values' ) );
				add_filter( 'woocommerce_checkout_get_value', array( $this, 'dibs_populate_fields' ), 10, 2 );
				add_filter( 'woocommerce_checkout_fields' ,  array( $this, 'dibs_set_not_required' ), 20 );
			}
		}

		// Add class if DIBS Easy is set as the default gateway
		add_filter( 'body_class', array( $this, 'dibs_add_body_class' ) );

		add_action( 'woocommerce_thankyou_dibs_easy', array( $this, 'dibs_thankyou' ) );
	}
	public function init_form_fields() {
		$this->form_fields = include( DIR_NAME . '/includes/dibs-settings.php' );
	}
	public function process_payment( $order_id, $retry = false ) {
		$order = wc_get_order( $order_id );

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

		//Get paymentID from order meta and set endpoint
		$charge_id = get_post_meta( $order_id, '_dibs_charge_id' )[0];

		// Add the sufix to the endpoint
		$endpoint_sufix = 'charges/' . $charge_id . '/refunds';

		// Make the request
		$request = new DIBS_Requests();
		$request = $request->make_request( 'POST', $body, $endpoint_sufix );
		if ( array_key_exists( 'refundId', $request ) ) { // Payment success
			$order->add_order_note( sprintf( __( 'Refund made in DIBS with charge ID %s. Reason: %s', 'dibs-easy-for-woocommerce' ), $request->refundId, $reason ) );
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
		if ( ! $order->has_status( array( 'processing', 'completed' ) ) ) {
			$payment_id = get_post_meta( $order_id, '_dibs_payment_id', true );
			$request = new DIBS_Requests();
			$request = $request->get_order_fields( $payment_id );
			if ( key_exists( 'reservedAmount', $request->payment->summary ) ) {
				$order->update_status( 'pending' );
				update_post_meta( $order_id, 'dibs_payment_type', $request->payment->paymentDetails->paymentType );
				update_post_meta( $order_id, 'dibs_customer_card', $request->payment->paymentDetails->cardDetails->maskedPan );
				$order->add_order_note( sprintf( __( 'Order made in DIBS with Payment ID %s. Payment type - %s.', 'dibs-easy-for-woocommerce' ), $payment_id, $request->payment->paymentDetails->paymentType ) );
				$order->payment_complete( $payment_id );
				WC()->cart->empty_cart();
			}
			WC()->session->__unset( 'dibs_incomplete_order' );
			WC()->session->__unset( 'order_awaiting_payment' );
		}
	}
	public function dibs_populate_fields( $value, $key ) {
		//Get the payment ID
		$payment_id = $_GET['paymentId'];

		$checkout_fields = $this->checkout_fields;

		// Check if order was processed correctly
		if ( key_exists( 'reservedAmount', $checkout_fields->payment->summary ) ) {
			//Set the values
			$first_name 	= (string) $checkout_fields->payment->consumer->privatePerson->firstName;
			$last_name  	= (string) $checkout_fields->payment->consumer->privatePerson->lastName;
			$email      	= (string) $checkout_fields->payment->consumer->privatePerson->email;
			$country    	= (string) dibs_get_iso_2_country( $checkout_fields->payment->consumer->shippingAddress->country );
			$address  		= (string) $checkout_fields->payment->consumer->shippingAddress->addressLine1;
			$city     		= (string) $checkout_fields->payment->consumer->shippingAddress->city;
			$postcode 		= (string) $checkout_fields->payment->consumer->shippingAddress->postalCode;
			$phone    		= (string) $checkout_fields->payment->consumer->privatePerson->phoneNumber->number;

			//Populate the fields
			switch ( $key ) {
				case 'billing_first_name':
					return $first_name;
					break;
				case 'billing_last_name':
					return $last_name;
					break;
				case 'billing_email':
					return $email;
					break;
				case 'billing_country':
					return $country;
					break;
				case 'billing_address_1':
					return $address;
					break;
				case 'billing_city':
					return $city;
					break;
				case 'billing_postcode':
					return $postcode;
					break;				
				case 'billing_phone':
					return $phone;
					break;
				case 'shipping_first_name':
					return $first_name;
					break;
				case 'shipping_last_name':
					return $last_name;
					break;
				case 'shipping_country':
					return $country;
					break;
				case 'shipping_address_1':
					return $address;
					break;
				case 'shipping_city':
					return $city;
					break;
				case 'shipping_postcode':
					return $postcode;
					break;
				case 'order_comments':
					return WC()->session->get( 'dibs_customer_order_note' );
					break;
			}
		} else {
			$order_id = WC()->session->get( 'dibs_incomplete_order' );
			$order = wc_get_order( $order_id );
			$order->add_order_note( sprintf( __( 'There was a problem with Payment ID %s.', 'dibs-easy-for-woocommerce' ), $payment_id ) );
			$redirect_url = add_query_arg( 'dibs-payment-id', $payment_id, trailingslashit( $order->get_cancel_order_url() ) );
			wp_redirect( $redirect_url );
			exit;
		} // End if().
	}

	public function dibs_set_not_required( $checkout_fields ) {
		//Set fields to not required, to prevent orders from failing
		if ( 'dibs_easy' === WC()->session->get( 'chosen_payment_method' ) ) {
			foreach ( $checkout_fields as $fieldset_key => $fieldset ) {
				foreach ( $fieldset as $field_key => $field ) {
					$checkout_fields[ $fieldset_key ][ $field_key ]['required'] = false;
				}
			}
		}
		return $checkout_fields;
	}
	public function dibs_get_field_values() {
		//Get the payment ID
		$payment_id = $_GET['paymentId'];

		$request = new DIBS_Requests();
		$this->checkout_fields = $request->get_order_fields( $payment_id );
		
		// Update customer country
		$country = (string) dibs_get_iso_2_country( $this->checkout_fields->payment->consumer->shippingAddress->country );
		$order_id = WC()->session->get( 'dibs_incomplete_order' );
		
		$this->prepare_cart_before_form_processing( $country );
		$this->prepare_local_order_before_form_processing( $order_id, $payment_id );
	}
	
	// Helper function to prepare the cart session before processing the order form
	public function prepare_cart_before_form_processing( $country = false ) {
		if( $country ) {
			WC()->customer->set_billing_country( $country );
			WC()->customer->set_shipping_country( $country );
			WC()->customer->save();
			WC()->cart->calculate_totals();
		}
	}
	
	// Helper function to prepare the local order before processing the order form
	public function prepare_local_order_before_form_processing( $order_id, $payment_id ) {
		// Update cart hash
		update_post_meta( $order_id, '_cart_hash', md5( json_encode( wc_clean( WC()->cart->get_cart_for_session() ) ) . WC()->cart->total ) );
		// Set the paymentID as a meta value to be used later for reference
		update_post_meta( $order_id, '_dibs_payment_id', $payment_id );
		// Order ready for processing
		WC()->session->set( 'order_awaiting_payment', $order_id );
		$order = wc_get_order( $order_id );
		$order->update_status( 'pending' );
	}
}// End of class DIBS_Easy_Gateway
