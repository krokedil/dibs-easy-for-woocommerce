<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Requests_Order {

	public static function get_order() {
		$items = DIBS_Requests_Items::get_items();

		return array(
			'items'     => $items,
			'amount'    => self::get_order_total( $items ),
			'currency'  => get_woocommerce_currency(),
			'shipping'  => array(
				'costSpecified' => true,
			),
		);
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
