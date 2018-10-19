<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Requests_Checkout {

	public static function get_checkout() {
		$checkout = array(
			'url'      => wc_get_checkout_url(),
			'termsUrl' => wc_get_page_permalink( 'terms' ),
			'shipping' => array(
				'countries'                   => array(),
				'merchantHandlesShippingCost' => true,
			),
		);
		if ( 'all' !== get_option( 'woocommerce_allowed_countries' ) ) {
			$checkout['ShippingCountries'] = self::get_shipping_countries();
		}
		$dibs_settings          = get_option( 'woocommerce_dibs_easy_settings' );
		$allowed_customer_types = ( isset( $dibs_settings['allowed_customer_types'] ) ) ? $dibs_settings['allowed_customer_types'] : 'B2C';
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
		} // End switch().

		return $checkout;
	}

	public static function get_shipping_countries() {
		$converted_countries      = array();
		$supported_dibs_countries = dibs_get_supported_countries();
		// Add shipping countries
		$wc_countries = new WC_Countries();
		$countries    = array_keys( $wc_countries->get_allowed_countries() );
		$i            = 0;
		foreach ( $countries as $country ) {
			$converted_country = dibs_get_iso_3_country( $country );
			if ( in_array( $converted_country, $supported_dibs_countries ) ) {
				$converted_countries[] = array( 'countryCode' => $converted_country );
				$i++;
				// DIBS only allow 5 countries
				if ( $i == 5 ) {
					break;
				}
			}
		}
		return $converted_countries;
	}
}
