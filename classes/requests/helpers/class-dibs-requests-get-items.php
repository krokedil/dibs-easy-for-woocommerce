<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Requests_Items {

	public static function get_items() {
		$items = array();

		// Get cart items.
		$cart_items = WC()->cart->get_cart_contents();
		foreach ( $cart_items as $cart_item ) {
			$items[] = self::get_item( $cart_item );
		}

		// Get cart fees.
		$cart_fees = WC()->cart->get_fees();
		foreach ( $cart_fees as $fee ) {
			$items[] = self::get_fees( $fee );
		}

		// Get cart shipping
		if ( WC()->cart->needs_shipping() ) {
			$shipping = self::get_shipping();
			if ( null !== $shipping ) {
				$items[] = $shipping;
			}
		}

		return $items;
	}

	public static function get_item( $cart_item ) {
		if ( $cart_item['variation_id'] ) {
			$product    = wc_get_product( $cart_item['variation_id'] );
			$product_id = $cart_item['variation_id'];
		} else {
			$product    = wc_get_product( $cart_item['product_id'] );
			$product_id = $cart_item['product_id'];
		}

		return array(
			'reference'        => self::get_sku( $product, $product_id ),
			'name'             => wc_dibs_clean_name( $product->get_title() ),
			'quantity'         => $cart_item['quantity'],
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
			'unitPrice'        => intval( round( ( $cart_item['line_total'] + $cart_item['line_tax'] ) / $cart_item['quantity'], 2 ) * 100 ),
			'taxRate'          => intval( round( $cart_item['line_tax'] / $cart_item['line_total'], 2 ) * 10000 ),
			'taxAmount'        => intval( round( $cart_item['line_tax'], 2 ) * 100 ),
			'grossTotalAmount' => intval( round( $cart_item['line_total'] + $cart_item['line_tax'], 2 ) * 100 ),
			'netTotalAmount'   => intval( round( $cart_item['line_total'], 2 ) * 100 ),
		);
	}

	public static function get_fees( $fee ) {
		return array(
			'reference'        => $fee->id,
			'name'             => wc_dibs_clean_name( $fee->name ),
			'quantity'         => 1,
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
			'unitPrice'        => $fee->amount * 100,
			'taxRate'          => intval( round( $fee->tax / $fee->amount, 2 ) * 10000 ),
			'taxAmount'        => intval( round( $fee->tax, 2 ) * 100 ),
			'grossTotalAmount' => intval( round( $fee->amount + $fee->tax, 2 ) * 100 ),
			'netTotalAmount'   => intval( round( $fee->amount - $fee->tax, 2 ) * 100 ),
		);
	}

	public static function get_shipping() {
		WC()->cart->calculate_shipping();
		$packages        = WC()->shipping->get_packages();
		$chosen_methods  = WC()->session->get( 'chosen_shipping_methods' );
		$chosen_shipping = $chosen_methods[0];
		foreach ( $packages as $i => $package ) {
			foreach ( $package['rates'] as $method ) {
				if ( $chosen_shipping === $method->id ) {
					if ( $method->cost > 0 ) {
						return array(
							'reference'        => '1',
							'name'             => wc_dibs_clean_name( $method->label ),
							'quantity'         => 1,
							'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
							'unitPrice'        => round( $method->cost + array_sum( $method->taxes ), 2 ) * 100,
							'taxRate'          => intval( round( array_sum( $method->taxes ) / $method->cost, 2 ) * 10000 ),
							'taxAmount'        => intval( round( array_sum( $method->taxes ), 2 ) * 100 ),
							'grossTotalAmount' => intval( round( $method->cost + array_sum( $method->taxes ), 2 ) * 100 ),
							'netTotalAmount'   => intval( round( $method->cost, 2 ) * 100 ),
						);
					} else {
						return array(
							'reference'        => '1',
							'name'             => wc_dibs_clean_name( $method->label ),
							'quantity'         => 1,
							'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
							'unitPrice'        => 0,
							'taxRate'          => 0,
							'taxAmount'        => 0,
							'grossTotalAmount' => 0,
							'netTotalAmount'   => 0,
						);
					}
				}
			}
		}
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
