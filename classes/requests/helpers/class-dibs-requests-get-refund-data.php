<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Get DIBS refund data.
 *
 * @class    DIBS_Get_Refund_Data
 * @package  DIBS/Classes/Requests/Helpers
 * @category Class
 * @author   Krokedil <info@krokedil.se>
 */
class DIBS_Requests_Get_Refund_Data {

	/**
	 * The refunded data.
	 *
	 * @var array
	 */
	public static $refund_data = array();

	/**
	 * Get total refund amount.
	 *
	 * @param int $order_id The order id.
	 * @return int
	 */
	public static function get_total_refund_amount( $order_id ) {
		$refund_order_id = self::get_refunded_order( $order_id );

		if ( null !== $refund_order_id ) {
			$refund_order        = wc_get_order( $refund_order_id );
			$total_refund_amount = intval( round( $refund_order->get_total() * 100 ) );

			return abs( $total_refund_amount );
		}
	}

	/**
	 * Get refund data
	 *
	 * @param int $order_id The order id.
	 * @return array
	 */
	public static function get_refund_data( $order_id ) {
		$refund_order_id = self::get_refunded_order( $order_id );

		if ( null !== $refund_order_id ) {
			// Get refund order data.
			$refund_order      = wc_get_order( $refund_order_id );
			$refunded_items    = $refund_order->get_items();
			$refunded_shipping = $refund_order->get_items( 'shipping' );
			$refunded_fees     = $refund_order->get_items( 'fee' );

			if ( $refunded_items ) {
				self::get_refunded_items( $order_id, $refunded_items );
			}

			if ( $refunded_shipping ) {
				self::get_refunded_shipping( $order_id, $refunded_shipping );
			}

			if ( $refunded_fees ) {
				self::get_refunded_fees( $order_id, $refunded_fees );
			}

			return self::$refund_data;
		}
	}

	/**
	 * Gets refunded order.
	 *
	 * @param int $order_id The order id.
	 * @return string
	 */
	public static function get_refunded_order( $order_id ) {
		$query_args      = array(
			'fields'         => 'id=>parent',
			'post_type'      => 'shop_order_refund',
			'post_status'    => 'any',
			'posts_per_page' => -1,
		);
		$refunds         = get_posts( $query_args );
		$refund_order_id = array_search( $order_id, $refunds );
		if ( is_array( $refund_order_id ) ) {
			foreach ( $refund_order_id as $key => $value ) {
				$refund_order_id = $value;
				break;
			}
		}
		return $refund_order_id;
	}

	/**
	 * Get refunded items.
	 *
	 * @param int   $order_id The order id.
	 * @param array $refunded_items_data Refunded items array.
	 * @return void
	 */
	private static function get_refunded_items( $order_id, $refunded_items_data ) {

		foreach ( $refunded_items_data as $item ) {
			$original_order = wc_get_order( $order_id );
			foreach ( $original_order->get_items() as $original_order_item ) {
				if ( $item->get_product_id() == $original_order_item->get_product_id() ) {
					// Found product match, continue.
					break;
				}
			}
			$product = $item->get_product();

			$sku                = empty( $product->get_sku() ) ? $product->get_id() : $product->get_sku();
			$name               = wc_dibs_clean_name( $item->get_name() );
			$quantity           = ( 0 === $item->get_quantity() ) ? 1 : $item->get_quantity();
			$unit               = __( 'pcs', 'dibs-easy-for-woocommerce' );
			$unit_price         = intval( round( ( $item->get_total() / abs( $quantity ) ) * 100 ) );
			$tax_rate           = intval( round( ( $item->get_total_tax() * 100 ) / ( $item->get_total() * 100 ) * 10000 ) );
			$tax_amount         = intval( round( $item->get_total_tax() * 100 ) );
			$gross_total_amount = intval( round( ( $item->get_total() + $item->get_total_tax() ) * 100 ) );
			$net_total_amount   = intval( round( $item->get_total() * 100 ) );

			$refunded_items = array(
				'reference'        => $sku,
				'name'             => $name,
				'quantity'         => abs( $quantity ),
				'unit'             => $unit,
				'unitPrice'        => abs( $unit_price ),
				'taxRate'          => abs( $tax_rate ),
				'taxAmount'        => abs( $tax_amount ),
				'grossTotalAmount' => abs( $gross_total_amount ),
				'netTotalAmount'   => abs( $net_total_amount ),
			);

			self::$refund_data[] = $refunded_items;
		}
	}

	/**
	 * Get refunded shipping.
	 *
	 * @param int   $order_id The order id.
	 * @param array $refunded_shipping_data Refunded shipping array.
	 * @return void
	 */
	private static function get_refunded_shipping( $order_id, $refunded_shipping_data ) {
		foreach ( $refunded_shipping_data as $shipping_item ) {
			$original_order = wc_get_order( $order_id );
			$free_shipping  = false;
			if ( 0 === intval( $shipping_item->get_total() ) ) {
				$free_shipping = true;
			}

			$reference          = 'Shipping';
			$name               = wc_dibs_clean_name( $shipping_item->get_name() );
			$quantity           = '1';
			$unit               = __( 'pcs', 'dibs-easy-for-woocommerce' );
			$unit_price         = ( $free_shipping ) ? 0 : intval( round( $shipping_item->get_total() * 100 ) );
			$tax_rate           = ( $free_shipping ) ? 0 : intval( round( ( $shipping_item->get_total_tax() * 100 ) / ( $shipping_item->get_total() * 100 ) * 10000 ) );
			$tax_amount         = ( $free_shipping ) ? 0 : intval( round( $shipping_item->get_total_tax() * 100 ) );
			$gross_total_amount = ( $free_shipping ) ? 0 : intval( round( ( $shipping_item->get_total() + $shipping_item->get_total_tax() ) * 100 ) );
			$net_total_amount   = ( $free_shipping ) ? 0 : intval( round( $shipping_item->get_total() * 100 ) );

			$refunded_shipping = array(
				'reference'        => $reference,
				'name'             => $name,
				'quantity'         => abs( $quantity ),
				'unit'             => $unit,
				'unitPrice'        => abs( $unit_price ),
				'taxRate'          => abs( $tax_rate ),
				'taxAmount'        => abs( $tax_amount ),
				'grossTotalAmount' => abs( $gross_total_amount ),
				'netTotalAmount'   => abs( $net_total_amount ),
			);

			self::$refund_data[] = $refunded_shipping;
		}
	}

	/**
	 * Get refunded fees.
	 *
	 * @param int   $order_id The order id.
	 * @param array $refunded_fees_data refunded fees array.
	 * @return void
	 */
	private static function get_refunded_fees( $order_id, $refunded_fees_data ) {
		foreach ( $refunded_fees_data as $fee_item ) {

			$reference          = 'Fee';
			$name               = wc_dibs_clean_name( $fee_item->get_name() );
			$quantity           = '1';
			$unit               = __( 'pcs', 'dibs-easy-for-woocommerce' );
			$unit_price         = intval( round( $fee_item->get_total() * 100 ) );
			$tax_rate           = intval( round( ( $fee_item->get_total_tax() * 100 ) / ( $fee_item->get_total() * 100 ) * 10000 ) );
			$tax_amount         = intval( round( $fee_item->get_total_tax() * 100 ) );
			$gross_total_amount = intval( round( ( $fee_item->get_total() + $fee_item->get_total_tax() ) * 100 ) );
			$net_total_amount   = intval( round( $fee_item->get_total() * 100 ) );

			$refunded_fees = array(
				'reference'        => $reference,
				'name'             => $name,
				'quantity'         => abs( $quantity ),
				'unit'             => $unit,
				'unitPrice'        => abs( $unit_price ),
				'taxRate'          => abs( $tax_rate ),
				'taxAmount'        => abs( $tax_amount ),
				'grossTotalAmount' => abs( $gross_total_amount ),
				'netTotalAmount'   => abs( $net_total_amount ),
			);

			self::$refund_data[] = $refunded_fees;
		}
	}


}
