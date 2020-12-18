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
class DIBS_Requests_Order {

	/**
	 * Gets formatted order.
	 *
	 * @param string $checkout_flow The checkout flow selected in settings (or set in plugin for specific occations).
	 * @param mixed  $order_id The WooCommerce order ID if one order exist.
	 *
	 * @return array
	 */
	public static function get_order( $checkout_flow = 'embedded', $order_id = null ) {
		if ( 'embedded' === $checkout_flow ) {
			$items = DIBS_Requests_Items::get_items();

			return array(
				'items'     => $items,
				'amount'    => self::get_order_total( $items ),
				'currency'  => get_woocommerce_currency(),
				'shipping'  => array(
					'costSpecified' => true,
				),
				'reference' => '1',
			);
		} else {
			$items = DIBS_Requests_Get_Order_Items::get_items( $order_id );
			$order = wc_get_order( $order_id );
			return array(
				'items'     => $items,
				'amount'    => intval( round( $order->get_total() * 100, 2 ) ),
				'currency'  => $order->get_currency(),
				'reference' => $order->get_order_number(),
			);
		}
	}

	/**
	 * Gets order total by calculating the sum of all order lines.
	 *
	 * @param array $items The order/cart line tems.
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
