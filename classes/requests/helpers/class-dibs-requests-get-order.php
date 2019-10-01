<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
class DIBS_Requests_Order {

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
				'amount'    => intval( round( $order->get_total() * 100 ) ),
				'currency'  => $order->get_currency(),
				'reference' => $order->get_order_number(),
			);
		}
	}

	public static function get_order_total( $items ) {
		$amount = 0;
		foreach ( $items as $item ) {
			foreach ( $item as $key => $value ) {
				if ( 'grossTotalAmount' == $key ) {
					$value   = intval( $value );
					$amount += $value;
				}
			}
		}
		return $amount;
	}
}
