<?php
/**
 * Formats the order information sent to Nets.
 *
 * @package DIBS_Easy/Classes/Requests/Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * DIBS_Requests_Order class.
 *
 * Class that formats the order information sent to Nets.
 */
class Nets_Easy_Order_Helper {

	/**
	 * Gets formatted order.
	 *
	 * @param string $checkout_flow The checkout flow selected in settings (or set in plugin for specific occasions).
	 * @param mixed  $order_id The WooCommerce order ID if one order exist.
	 *
	 * @return array
	 */
	public static function get_order( $checkout_flow = 'embedded', $order_id = null ) {
		if ( 'embedded' === $checkout_flow ) {
			$items = Nets_Easy_Cart_Helper::get_items();

			return array(
				'items'     => $items,
				'amount'    => self::get_order_total( $items ),
				'currency'  => get_woocommerce_currency(),
				'shipping'  => array(
					'costSpecified' => true,
				),
				'reference' => apply_filters( 'nets_easy_embedded_order_reference', '1' ),
			);
		}

		$items = Nets_Easy_Order_Items_Helper::get_items( $order_id );
		$order = wc_get_order( $order_id );

		return array(
			'items'     => $items,
			'amount'    => intval( round( $order->get_total() * 100, 2 ) ),
			'currency'  => $order->get_currency(),
			'reference' => $order->get_order_number(),
		);
	}

	/**
	 * Gets order total by calculating the sum of all order lines.
	 *
	 * @param array $items The order/cart line items.
	 *
	 * @return string
	 */
	public static function get_order_total( $items ) {
		$amount = 0;
		foreach ( $items as $item ) {
			foreach ( $item as $key => $value ) {
				if ( 'grossTotalAmount' === $key ) {
					$amount += $value;
				}
			}
		}
		// Amount already rounded and converted to minor units.
		return $amount;
	}
}
