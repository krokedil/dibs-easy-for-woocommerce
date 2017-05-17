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
		if ( is_checkout() ) {
			if ( isset( $_GET['paymentId'] ) ) {
				add_filter( 'woocommerce_checkout_fields' ,  array( $this, 'dibs_populate_fields' ) );
			}
			// Add class if DIBS Easy is set as the gateway in session
			$selected_gateway = WC()->session->chosen_payment_method;
			if ( 'dibs_easy' == $selected_gateway ) {
				add_filter( 'body_class', array( $this, 'dibs_add_body_class' ) );
			}
		}
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
	public function dibs_thankyou( $order_id ) {
		$order = wc_get_order( $order_id );

		WC()->cart->empty_cart();
		$order->payment_complete();

		WC()->session->__unset( 'dibs_incomplete_order' );
		WC()->session->__unset( 'order_awaiting_payment' );
	}
	public function dibs_populate_fields() {
		//Get the payment ID
		$payment_id = $_GET['paymentId'];

		$request = new DIBS_Requests();
		$request = $request->get_order_fields( $payment_id );

		//Set the values
		$first_name = (string) $request->payment->consumer->privatePerson->firstName;
		$last_name = (string) $request->payment->consumer->privatePerson->lastName;
		$email = (string) $request->payment->consumer->privatePerson->email;
		$country = (string) $request->payment->consumer->shippingAddress->country;
		if ( 'SWE' === $country ) {
			$country = 'SE';
		}
		$address = (string) $request->payment->consumer->shippingAddress->addressLine1;
		$city = (string) $request->payment->consumer->shippingAddress->city;
		$postcode = (string) $request->payment->consumer->shippingAddress->postalCode;
		$phone = (string) $request->payment->consumer->privatePerson->phoneNumber->number;

		//Populate the fields
		$fields['billing']['billing_first_name']['default'] = $first_name;
		$fields['billing']['billing_last_name']['default'] = $last_name;
		$fields['billing']['billing_email']['default'] = $email;
		$fields['billing']['billing_country']['default'] = $country;
		$fields['billing']['billing_address_1']['default'] = $address;
		$fields['billing']['billing_city']['default'] = $city;
		$fields['billing']['billing_postcode']['default'] = $postcode;
		$fields['billing']['billing_phone']['default'] = $phone;

		return $fields;
	}
}// End of class DIBS_Easy_Gateway
