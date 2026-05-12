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
	public static function get_checkout( $checkout_flow = 'inline', $order_id = null ) {
		$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		$auto_capture  = $dibs_settings['auto_capture'] ?? 'no';

		$checkout = array(
			'termsUrl' => wc_get_page_permalink( 'terms' ),
		);
		if ( nexi_is_embedded( $checkout_flow ) ) {
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

			$checkout['consumer']    = self::prefill_embedded_customer_data();
			$checkout['countryCode'] = dibs_get_iso_3_country( WC()->customer->get_billing_country() );

			if ( 'inline' === $checkout_flow ) {
				$checkout['merchantHandlesConsumerData'] = true;
			}
		} else {
			$order      = wc_get_order( $order_id );
			$return_url = add_query_arg( 'easy_confirm', 'yes', $order->get_checkout_order_received_url() );
			$cancel_url = 'admin' === $order->get_created_via() ? $order->get_checkout_payment_url() : wc_get_checkout_url();

			if ( 'overlay' === $checkout_flow ) {
				$return_url = add_query_arg( array( 'nets_reload' => 'true' ), $return_url );
				$cancel_url = add_query_arg( array( 'nexi_overlay' => 'true' ), home_url() );
			}

			$checkout['returnUrl']                               = esc_url_raw( $return_url );
			$checkout['integrationType']                         = 'HostedPaymentPage';
			$checkout['cancelUrl']                               = $cancel_url;
			$checkout['merchantHandlesConsumerData']             = true;
			$checkout['shipping']['countries']                   = array();
			$checkout['shipping']['merchantHandlesShippingCost'] = false;
			$checkout['consumer']                                = self::get_consumer_address( $order );
			$checkout['countryCode']                             = dibs_get_iso_3_country( $order->get_billing_country() );
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
	 * @param WC_Order $order The WooCommerce order.
	 * @return array
	 */
	public static function get_consumer_address( $order ) {
		$consumer          = array();
		$consumer['email'] = $order->get_billing_email();

		if ( ! empty( $order->get_billing_address_1() ) ) {
			$consumer['shippingAddress']['addressLine1'] = $order->get_billing_address_1();
		}

		if ( ! empty( $order->get_billing_address_2() ) ) {
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

		$phone = self::format_phone( $order->get_billing_phone(), $order->get_billing_country() );
		if ( ! empty( $phone['number'] ) ) {
			$consumer['phoneNumber'] = array(
				'prefix' => $phone['prefix'],
				'number' => $phone['number'],
			);
		}

		$dibs_settings          = get_option( 'woocommerce_dibs_easy_settings' );
		$allowed_customer_types = $dibs_settings['allowed_customer_types'] ?? 'B2C';

		if ( $order->get_billing_company() && in_array( $allowed_customer_types, array( 'B2B', 'B2CB', 'B2BC' ), true ) ) {
			$consumer['company']['name']                 = $order->get_billing_company();
			$consumer['company']['contact']['firstName'] = $order->get_billing_first_name();
			$consumer['company']['contact']['lastName']  = $order->get_billing_last_name();

			$has_person = array_filter( $consumer['company']['contact'] );
			if ( count( $has_person ) <= 1 ) {
				unset( $consumer['contact'] );
			}
		} else {
			$consumer['privatePerson']['firstName'] = $order->get_billing_first_name();
			$consumer['privatePerson']['lastName']  = $order->get_billing_last_name();

			$has_person = array_filter( $consumer['privatePerson'] );
			if ( count( $has_person ) <= 1 ) {
				unset( $consumer['privatePerson'] );
			}
		}

		return $consumer;
	}

	/**
	 * Splits a billing phone number into the prefix/number shape Nets expects.
	 *
	 * @param string $phone   Billing phone as entered by the customer.
	 * @param string $country ISO-2 billing country code, used to look up the calling code.
	 * @return array{prefix: ?string, number: ?string}
	 */
	public static function format_phone( $phone, $country ) {
		$sanitized = wc_sanitize_phone_number( (string) $phone );
		if ( '' === $sanitized ) {
			return array(
				'prefix' => null,
				'number' => null,
			);
		}

		$country_codes = self::get_country_calling_codes( $country );

		if ( strpos( $sanitized, '+' ) === 0 ) {
			$prefix = self::detect_calling_code( $sanitized, $country_codes );
			$number = '' !== $prefix ? substr( $sanitized, strlen( $prefix ) ) : $sanitized;
		} else {
			// First entry is the canonical code; arrays only occur for NANP territories (PR, DO).
			$prefix = $country_codes[0] ?? '';
			$number = ltrim( $sanitized, '0' );
		}

		return array(
			'prefix' => '' !== $prefix ? $prefix : null,
			'number' => preg_match( '/\d/', $number ) ? $number : null,
		);
	}

	/**
	 * Detects which calling code a `+`-prefixed phone number starts with.
	 *
	 * Prefers the billing country's codes (longest match wins); falls back to the
	 * full WooCommerce list so e.g. `+1`, `+46` and `+354` are all handled.
	 *
	 * @param string   $sanitized_phone Phone number starting with `+`.
	 * @param string[] $country_codes   Calling codes for the billing country.
	 * @return string The detected calling code, or `''` if none matched.
	 */
	private static function detect_calling_code( $sanitized_phone, array $country_codes ) {
		foreach ( self::sort_by_length_desc( $country_codes ) as $code ) {
			if ( strpos( $sanitized_phone, $code ) === 0 ) {
				return $code;
			}
		}

		foreach ( self::sort_by_length_desc( self::all_calling_codes() ) as $code ) {
			if ( strpos( $sanitized_phone, $code ) === 0 ) {
				return $code;
			}
		}

		return '';
	}

	/**
	 * Returns the calling codes registered for a country.
	 *
	 * WC stores most countries as a single string but a few NANP territories (PR, DO)
	 * as arrays of multiple area-code-included codes. This normalizes both shapes.
	 *
	 * @param string $country ISO-2 country code.
	 * @return string[]
	 */
	private static function get_country_calling_codes( $country ) {
		if ( ! $country ) {
			return array();
		}

		$code = WC()->countries->get_country_calling_code( $country );
		if ( is_array( $code ) ) {
			return array_values( array_filter( $code, 'strlen' ) );
		}

		return '' !== (string) $code ? array( (string) $code ) : array();
	}

	/**
	 * Returns every distinct calling code WooCommerce knows about, with array entries flattened.
	 *
	 * @return string[]
	 */
	private static function all_calling_codes() {
		$codes = array();
		foreach ( WC()->countries->get_country_calling_codes() as $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $entry ) {
					if ( '' !== (string) $entry ) {
						$codes[] = $entry;
					}
				}
			} elseif ( '' !== (string) $value ) {
				$codes[] = $value;
			}
		}
		return array_values( array_unique( $codes ) );
	}

	/**
	 * @param string[] $codes
	 * @return string[]
	 */
	private static function sort_by_length_desc( array $codes ) {
		usort(
			$codes,
			static function ( $a, $b ) {
				return strlen( (string) $b ) - strlen( (string) $a );
			}
		);
		return $codes;
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
		 * @var WC_Customer $customer
		 */
		$customer          = WC()->customer;
		$email             = $customer->get_billing_email();
		$consumer['email'] = $email;

		if ( ! empty( $customer->get_billing_address_1() ) ) {
			$consumer['shippingAddress']['addressLine1'] = $customer->get_billing_address_1();
		}

		if ( ! empty( $customer->get_billing_address_2() ) ) {
			$consumer['shippingAddress']['addressLine2'] = $customer->get_billing_address_2();
		}

		$has_address = isset( $consumer['shippingAddress']['addressLine1'] );
		if ( ! $has_address ) {
			unset( $consumer['shippingAddress'] );
		}

		if ( ! empty( $customer->get_billing_postcode() ) ) {
			$postal_code                               = str_replace( ' ', '', $customer->get_billing_postcode() );
			$consumer['shippingAddress']['postalCode'] = $postal_code;
		}

		// If any of these fields is set, the other must be set too.
		if ( $has_address ) {
			$consumer['shippingAddress']['country'] = dibs_get_iso_3_country( $customer->get_billing_country() );
			if ( ! empty( $customer->get_billing_city() ) ) {
				$consumer['shippingAddress']['city'] = $customer->get_billing_city();
			}
		}

		$phone = self::format_phone(
			WC()->customer->get_billing_phone(),
			WC()->checkout()->get_value( 'billing_country' )
		);
		if ( ! empty( $phone['number'] ) ) {
			$consumer['phoneNumber'] = array(
				'prefix' => $phone['prefix'],
				'number' => $phone['number'],
			);
		}

		$dibs_settings          = get_option( 'woocommerce_dibs_easy_settings' );
		$allowed_customer_types = $dibs_settings['allowed_customer_types'] ?? 'B2C';

		if ( $customer->get_billing_company() && in_array( $allowed_customer_types, array( 'B2B', 'B2CB', 'B2BC' ), true ) ) {
			$consumer['company']['name']                 = $customer->get_billing_company();
			$consumer['company']['contact']['firstName'] = $customer->get_billing_first_name();
			$consumer['company']['contact']['lastName']  = $customer->get_billing_last_name();

			$has_contact = array_filter( $consumer['company']['contact'] ?? array() );
			if ( count( $has_contact ) <= 1 ) {
				unset( $consumer['company']['contact'] );
			}
		} else {
			$consumer['privatePerson']['firstName'] = $customer->get_billing_first_name();
			$consumer['privatePerson']['lastName']  = $customer->get_billing_last_name();

			$has_person = array_filter( $consumer['privatePerson'] ?? array() );
			if ( count( $has_person ) <= 1 ) {
				unset( $consumer['privatePerson'] );
			}
		}

		return $consumer;
	}

}
