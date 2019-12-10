<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Requests_Get_Order_Items {
	/**
	 * DIBS Settings
	 *
	 * @var mixed
	 */
	// public static $dibs_settings;

	public static function get_items( $order_id ) {
		$order = wc_get_order( $order_id );
		$items = array();

		// Get order items.
		foreach ( $order->get_items() as $order_item ) {
			$items[] = self::get_item( $order_item );
		}

		// Get order fees.
		foreach ( $order->get_fees() as $order_fee ) {
			$items[] = self::get_fees( $order_fee );
		}

		// Get order shipping
		foreach ( $order->get_shipping_methods() as $shipping_method ) {
			$items[] = self::get_shipping( $shipping_method );
		}

		return $items;
	}

	public static function get_item( $order_item ) {
		$product = $order_item->get_product();
		if ( $order_item['variation_id'] ) {
			$product_id = $order_item['variation_id'];
		} else {
			$product_id = $order_item['product_id'];
		}

		return array(
			'reference'        => self::get_sku( $product, $product_id ),
			'name'             => wc_dibs_clean_name( $product->get_name() ),
			'quantity'         => $order_item['qty'],
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
			'unitPrice'        => intval( round( ( $order_item->get_total() / $order_item['qty'] ) * 100 ) ),
			'taxRate'          => intval( round( ( $order_item->get_total_tax() / $order_item->get_total() ) * 10000 ) ),
			'taxAmount'        => intval( round( $order_item->get_total_tax() * 100 ) ),
			'grossTotalAmount' => intval( round( ( $order_item->get_total() + $order_item->get_total_tax() ) * 100 ) ),
			'netTotalAmount'   => intval( round( $order_item->get_total() * 100 ) ),
		);
	}

	public static function get_fees( $order_fee ) {
		$fee_reference    = 'Fee';
		$invoice_fee_name = '';
		$dibs_settings    = get_option( 'woocommerce_dibs_easy_settings' );
		$invoice_fee_id   = isset( $dibs_settings['dibs_invoice_fee'] ) ? $dibs_settings['dibs_invoice_fee'] : '';

		if ( $invoice_fee_id ) {
			$_product         = wc_get_product( $invoice_fee_id );
			$invoice_fee_name = $_product->get_name();
		}

		// Check if the refunded fee is the invoice fee.
		if ( $invoice_fee_name === $order_fee->get_name() ) {
			$fee_reference = self::get_sku( $_product, $_product->get_id() );
		} else {
			// Format the fee name so it match the same fee in Collector.
			$fee_name      = str_replace( ' ', '-', strtolower( $order_fee->get_name() ) );
			$fee_reference = 'fee|' . $fee_name;
		}

		return array(
			'reference'        => $fee_reference,
			'name'             => wc_dibs_clean_name( $order_fee->get_name() ),
			'quantity'         => '1',
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
			'unitPrice'        => intval( round( $order_fee->get_total() * 100 ) ),
			'taxRate'          => intval( round( ( $order_fee->get_total_tax() / $order_fee->get_total() ) * 10000 ) ),
			'taxAmount'        => intval( round( $order_fee->get_total_tax() * 100 ) ),
			'grossTotalAmount' => intval( round( ( $order_fee->get_total() + $order_fee->get_total_tax() ) * 100 ) ),
			'netTotalAmount'   => intval( round( $order_fee->get_total() * 100 ) ),
		);
	}

	public static function get_shipping( $shipping_method ) {
		$free_shipping = false;
		if ( 0 === intval( $shipping_method->get_total() ) ) {
			$free_shipping = true;
		}
		if ( null !== $shipping_method->get_instance_id() ) {
			$shipping_reference = 'shipping|' . $shipping_method->get_method_id() . ':' . $shipping_method->get_instance_id();
		} else {
			$shipping_reference = 'shipping|' . $shipping_method->get_method_id();
		}

		return array(
			'reference'        => $shipping_reference,
			'name'             => wc_dibs_clean_name( $shipping_method->get_method_title() ),
			'quantity'         => '1',
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
			'unitPrice'        => ( $free_shipping ) ? 0 : intval( round( $shipping_method->get_total() * 100 ) ),
			'taxRate'          => ( $free_shipping ) ? 0 : intval( round( ( $shipping_method->get_total_tax() / $shipping_method->get_total() ) * 10000 ) ),
			'taxAmount'        => ( $free_shipping ) ? 0 : intval( round( $shipping_method->get_total_tax() * 100 ) ),
			'grossTotalAmount' => ( $free_shipping ) ? 0 : intval( round( ( $shipping_method->get_total() + $shipping_method->get_total_tax() ) * 100 ) ),
			'netTotalAmount'   => ( $free_shipping ) ? 0 : intval( round( $shipping_method->get_total() * 100 ) ),
		);
	}

	public static function get_sku( $product, $product_id ) {
		if ( get_post_meta( $product_id, '_sku', true ) !== '' ) {
			$part_number = $product->get_sku();
		} else {
			$part_number = $product->get_id();
		}
		return substr( $part_number, 0, 32 );
	}
}
