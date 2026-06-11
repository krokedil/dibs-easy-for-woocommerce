<?php
/**
 * Creates a WooCommerce order from a completed Nexi Express payment.
 *
 * @package Krokedil\NexiCheckout\ExpressButton
 */

namespace Krokedil\Nexi\ExpressButton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WC order creation for Express Button payments.
 */
class OrderHandler {

	/**
	 * Creates a WooCommerce order from a completed Nexi payment.
	 *
	 * @param string $payment_id The Nexi payment ID.
	 * @return string|\WP_Error Order received URL on success, WP_Error on failure.
	 */
	public function create_order( string $payment_id ) {
		// Prevent duplicates: if an order already exists for this payment_id, return its URL.
		$existing = $this->find_existing_order( $payment_id );
		if ( $existing ) {
			\Nets_Easy_Logger::log( "[Express] Duplicate paymentcompleted for $payment_id – returning existing order $existing." );
			return wc_get_order( $existing )->get_checkout_order_received_url();
		}

		$nexi_order = Nets_Easy()->api->get_nets_easy_order( $payment_id );
		if ( is_wp_error( $nexi_order ) ) {
			return $nexi_order;
		}

		$payment   = $nexi_order['payment'] ?? [];
		$consumer  = $payment['consumer'] ?? [];
		$address   = $consumer['shippingAddress'] ?? [];
		$person    = $consumer['privatePerson'] ?? $consumer['company']['contact'] ?? [];
		$phone_obj = $consumer['phoneNumber'] ?? [];

		$country_iso2 = strlen( $address['country'] ?? '' ) > 2
			? dibs_get_iso_2_country( $address['country'] )
			: ( $address['country'] ?? '' );

		$first_name = $person['firstName'] ?? '';
		$last_name  = $person['lastName'] ?? '';
		$email      = $consumer['email'] ?? '';
		$phone      = ( $phone_obj['prefix'] ?? '' ) . ( $phone_obj['number'] ?? '' );
		$address1   = $address['addressLine1'] ?? '';
		$city       = $address['city'] ?? '';
		$postcode   = $address['postalCode'] ?? '';

		$order = wc_create_order(
			[
				'payment_method'       => 'dibs_easy',
				'payment_method_title' => __( 'Nexi Checkout', 'dibs-easy-for-woocommerce' ),
				'status'               => 'pending',
			]
		);

		if ( is_wp_error( $order ) ) {
			return $order;
		}

		// Billing.
		$order->set_billing_first_name( $first_name );
		$order->set_billing_last_name( $last_name );
		$order->set_billing_email( $email );
		$order->set_billing_phone( $phone );
		$order->set_billing_address_1( $address1 );
		$order->set_billing_city( $city );
		$order->set_billing_postcode( $postcode );
		$order->set_billing_country( $country_iso2 );

		// Shipping (same as billing for Express).
		$order->set_shipping_first_name( $first_name );
		$order->set_shipping_last_name( $last_name );
		$order->set_shipping_address_1( $address1 );
		$order->set_shipping_city( $city );
		$order->set_shipping_postcode( $postcode );
		$order->set_shipping_country( $country_iso2 );

		// Product line.
		$product_ctx = WC()->session->get( 'nexi_express_product' );
		if ( ! empty( $product_ctx ) ) {
			$product = wc_get_product( $product_ctx['product_id'] );
			if ( $product ) {
				$order->add_product( $product, $product_ctx['quantity'] );
			}
		}

		// Shipping line.
		$shipping_item = WC()->session->get( 'nexi_express_shipping' );
		if ( ! empty( $shipping_item ) ) {
			$shipping_line = new \WC_Order_Item_Shipping();
			$shipping_line->set_method_title( $shipping_item['name'] );
			$shipping_line->set_method_id( str_replace( 'shipping|', '', $shipping_item['reference'] ) );
			$shipping_line->set_total( $shipping_item['netTotalAmount'] / 100 );
			$order->add_item( $shipping_line );
		}

		$order->calculate_totals();
		$order->update_meta_data( '_dibs_payment_id', $payment_id );
		$order->update_meta_data( '_nexi_express_order', 'yes' );
		$order->update_status( 'on-hold', __( 'Nexi Express payment received.', 'dibs-easy-for-woocommerce' ) );

		// Update Nexi payment with the WC order reference.
		Nets_Easy()->api->update_nets_easy_order_reference( $payment_id, $order->get_id() );

		\Nets_Easy_Logger::log( "[Express] WC order {$order->get_id()} created for payment $payment_id." );

		return $order->get_checkout_order_received_url();
	}

	/**
	 * Checks whether a WC order already exists for the given payment ID.
	 *
	 * @param string $payment_id Nexi payment ID.
	 * @return int|null Order ID or null.
	 */
	private function find_existing_order( string $payment_id ): ?int {
		$query = new \WC_Order_Query(
			[
				'limit'          => 1,
				'return'         => 'ids',
				'payment_method' => 'dibs_easy',
				'date_created'   => '>' . ( time() - DAY_IN_SECONDS ),
			]
		);

		foreach ( $query->get_orders() as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( strtolower( $order->get_meta( '_dibs_payment_id' ) ) === strtolower( $payment_id ) ) {
				return (int) $order_id;
			}
		}

		return null;
	}
}
