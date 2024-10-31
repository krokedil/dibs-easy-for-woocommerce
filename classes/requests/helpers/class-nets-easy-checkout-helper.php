<?php
/**
 * Class that formats the checkout section.
 *
 * @package DIBS_Easy/Classes/Requests/Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * DIBS_Requests_Checkout class.
 *
 * Class that gets the checkout data for the order.
 */
class Nets_Easy_Checkout_Helper {

	/**
	 * Gets merchant checkout for Easy purchase.
	 *
	 * @param  string $checkout_flow Embedded or Redirect.
	 * @param  mixed  $order_id WooCommerce order ID or null.
	 *
	 * @return array
	 */
	public static function get_checkout( $checkout_flow = 'embedded', $order_id = null ) {
		$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		$auto_capture  = $dibs_settings['auto_capture'] ?? 'no';

		$checkout = array(
			'termsUrl' => wc_get_page_permalink( 'terms' ),
		);
		if ( 'embedded' === $checkout_flow ) {
			$checkout['url']                   = wc_get_checkout_url();
			$checkout['shipping']['countries'] = array();

			// If WooCommerce needs an address before calculating shipping, let's set merchantHandlesShippingCost to true.
			if ( 'yes' === get_option( 'woocommerce_shipping_cost_requires_address' ) ) {
				$checkout['shipping']['merchantHandlesShippingCost'] = true;
			} else {
				$checkout['shipping']['merchantHandlesShippingCost'] = false;
			}

			if ( 'all' !== get_option( 'woocommerce_allowed_countries' ) ) {
				$checkout['shipping']['countries'] = self::get_shipping_countries();
			}

			$checkout['consumer'] = self::prefill_embedded_customer_data();

		} else {
			$order      = wc_get_order( $order_id );
			$return_url = add_query_arg( 'easy_confirm', 'yes', $order->get_checkout_order_received_url() );
			$cancel_url = 'admin' === $order->get_created_via() ? $order->get_checkout_payment_url() : wc_get_checkout_url();

			if ( 'overlay' === $checkout_flow ) {
				$return_url = add_query_arg( array( 'nets_reload' => 'true' ), $return_url );
			}

			$checkout['returnUrl']                               = esc_url_raw( $return_url );
			$checkout['integrationType']                         = 'HostedPaymentPage';
			$checkout['cancelUrl']                               = $cancel_url;
			$checkout['merchantHandlesConsumerData']             = true;
			$checkout['shipping']['countries']                   = array();
			$checkout['shipping']['merchantHandlesShippingCost'] = false;
			$checkout['consumer']                                = self::get_consumer_address( $order );
		}

		$allowed_customer_types = $dibs_settings['allowed_customer_types'] ?? 'B2C';
		switch ( $allowed_customer_types ) {
			case 'B2C':
				$checkout['consumerType']['supportedTypes'] = array( 'B2C' );
				break;
			case 'B2B':
				$checkout['consumerType']['supportedTypes'] = array( 'B2B' );
				break;
			case 'B2CB':
				$checkout['consumerType']['supportedTypes'] = array( 'B2C', 'B2B' );
				$checkout['consumerType']['default']        = 'B2C';
				break;
			case 'B2BC':
				$checkout['consumerType']['supportedTypes'] = array( 'B2B', 'B2C' );
				$checkout['consumerType']['default']        = 'B2B';
				break;
			default:
				$checkout['consumerType']['supportedTypes'] = array( 'B2B' );
		}

		// Capture transaction directly when reservation has been accepted?
		// https://developers.nets.eu/nets-easy/en-EU/api/payment-v1/#v1-payments-post-body-checkout-charge.
		if ( 'yes' === $auto_capture ) {
			$checkout['charge'] = true;
		}

		return $checkout;
	}

	/**
	 * Gets formatted shipping countries.
	 *
	 * @return array
	 */
	public static function get_shipping_countries() {
		$converted_countries      = array();
		$supported_dibs_countries = dibs_get_supported_countries();
		// Add shipping countries.
		$wc_countries = new WC_Countries();
		$countries    = array_keys( $wc_countries->get_allowed_countries() );

		foreach ( $countries as $country ) {
			$converted_country = dibs_get_iso_3_country( $country );
			if ( in_array( $converted_country, $supported_dibs_countries, true ) ) {
				$converted_countries[] = array( 'countryCode' => $converted_country );
			}
		}
		return $converted_countries;
	}

	/**
	 * Gets customer address.
	 *
	 * @param object $order The WooCommerce order.
	 * @return array
	 */
	public static function get_consumer_address( $order ) {
		$consumer          = array();
		$consumer['email'] = $order->get_billing_email();

		if ( $order->get_billing_address_1() ) {
			$consumer['shippingAddress']['addressLine1'] = $order->get_billing_address_1();
		}

		if ( $order->get_billing_address_2() ) {
			$consumer['shippingAddress']['addressLine2'] = $order->get_billing_address_2();
		}

		if ( $order->get_billing_postcode() ) {
			$postal_code = null;
			if ( ! empty( $order->get_billing_postcode() ) ) {
				$postal_code = str_replace( ' ', '', $order->get_billing_postcode() );
			}
			$consumer['shippingAddress']['postalCode'] = $postal_code;
		}

		if ( $order->get_billing_city() ) {
			$consumer['shippingAddress']['city'] = $order->get_billing_city();
		}

		$consumer['shippingAddress']['country'] = dibs_get_iso_3_country( $order->get_billing_country() );

		if ( $order->get_billing_phone() ) {
			$consumer['phoneNumber']['prefix'] = self::get_phone_prefix( $order );
			$consumer['phoneNumber']['number'] = self::get_phone_number( $order );
		}

		$dibs_settings          = get_option( 'woocommerce_dibs_easy_settings' );
		$allowed_customer_types = $dibs_settings['allowed_customer_types'] ?? 'B2C';

		if ( $order->get_billing_company() && in_array( $allowed_customer_types, array( 'B2B', 'B2CB', 'B2BC' ), true ) ) {
			$consumer['company']['name']                 = $order->get_billing_company();
			$consumer['company']['contact']['firstName'] = $order->get_billing_first_name();
			$consumer['company']['contact']['lastName']  = $order->get_billing_last_name();
		} else {
			$consumer['privatePerson']['firstName'] = $order->get_billing_first_name();
			$consumer['privatePerson']['lastName']  = $order->get_billing_last_name();
		}
		return $consumer;
	}

	/**
	 * Gets customer phone prefix formatted for Nets.
	 *
	 * @param object $order The WooCommerce order.
	 * @return string
	 */
	public static function get_phone_prefix( $order ) {
		$prefix = null;
		if ( substr( $order->get_billing_phone(), 0, 1 ) === '+' ) {
			$prefix = substr( $order->get_billing_phone(), 0, 3 );
		} else {
			$prefix = dibs_get_phone_prefix_for_country( $order->get_billing_country() );
		}
		return $prefix;
	}

	/**
	 * Gets customer phone number formatted for Nets.
	 *
	 * @param object $order The WooCommerce order.
	 * @return string
	 */
	public static function get_phone_number( $order ) {
		$phone_number = $order->get_billing_phone();

		if ( ! $phone_number ) {
			return null;
		}

		if ( substr( $phone_number, 0, 1 ) === '+' ) {
			$phone_number = substr( $phone_number, strlen( self::get_phone_prefix( $order ) ) );
		}

		$phone_number = str_replace( array( '-', ' ' ), '', $phone_number );

		return $phone_number;
	}

	/**
	 * Prefill customer data in embedded checkout.
	 *
	 * @return array
	 */
	protected static function prefill_embedded_customer_data() {
		$consumer = array();
		/**
		 * The customer object.
		 *
		 * @var $customer WC_Customer
		 */
		$customer          = WC()->customer;
		$email             = $customer->get_billing_email();
		$consumer['email'] = $email;

		if ( $customer->get_billing_address_1() ) {
			$consumer['shippingAddress']['addressLine1'] = $customer->get_billing_address_1();
		}

		if ( $customer->get_billing_address_2() ) {
			$consumer['shippingAddress']['addressLine2'] = $customer->get_billing_address_2();
		}

		if ( $customer->get_billing_postcode() ) {
			$postal_code = null;
			if ( ! empty( $customer->get_billing_postcode() ) ) {
				$postal_code = str_replace( ' ', '', $customer->get_billing_postcode() );
			}
			$consumer['shippingAddress']['postalCode'] = $postal_code;
		}

		if ( $customer->get_billing_city() ) {
			$consumer['shippingAddress']['city'] = $customer->get_billing_city();
		}

		$consumer['shippingAddress']['country'] = dibs_get_iso_3_country( $customer->get_billing_country() );

		$consumer['phoneNumber']['prefix'] = self::get_checkout_phone_prefix();
		$consumer['phoneNumber']['number'] = self::get_checkout_phone_number();

		$dibs_settings          = get_option( 'woocommerce_dibs_easy_settings' );
		$allowed_customer_types = $dibs_settings['allowed_customer_types'] ?? 'B2C';

		if ( $customer->get_billing_company() && in_array( $allowed_customer_types, array( 'B2B', 'B2CB', 'B2BC' ), true ) ) {
			$consumer['company']['name']                 = $customer->get_billing_company();
			$consumer['company']['contact']['firstName'] = $customer->get_billing_first_name();
			$consumer['company']['contact']['lastName']  = $customer->get_billing_last_name();
		} else {
			$consumer['privatePerson']['firstName'] = $customer->get_billing_first_name();
			$consumer['privatePerson']['lastName']  = $customer->get_billing_last_name();
		}
		return $consumer;
	}

	/**
	 * Gets customer phone prefix formatted for Nets.
	 *
	 * @return string
	 */
	public static function get_checkout_phone_prefix() {
		$prefix       = null;
		$phone_number = WC()->customer->get_billing_phone();
		if ( substr( $phone_number, 0, 1 ) === '+' ) {
			$prefix = substr( $phone_number, 0, 3 );
		} else {
			$prefix = dibs_get_phone_prefix_for_country( WC()->checkout()->get_value( 'billing_country' ) );
		}
		return $prefix;
	}

	/**
	 * Gets customer phone number formatted for Nets.
	 *
	 * @return string
	 */
	public static function get_checkout_phone_number() {
		$billing_phone = WC()->customer->get_billing_phone();
		if ( substr( $billing_phone, 0, 1 ) === '+' ) {
			$phone_number = substr( $billing_phone, strlen( self::get_checkout_phone_prefix() ) );
			$phone_number = str_replace( ' ', '', $phone_number );
		} else {
			$phone_number = str_replace( '-', '', $billing_phone );
			$phone_number = str_replace( ' ', '', $phone_number );
		}
		return $phone_number;
	}
}
