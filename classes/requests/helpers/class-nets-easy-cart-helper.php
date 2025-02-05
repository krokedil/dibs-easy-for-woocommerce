<?php
/**
 * Formats the cart items sent to Nets.
 *
 * @package DIBS_Easy/Classes/Requests/Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use KrokedilNexiCheckoutDeps\Krokedil\WooCommerce\OrderLineData as OrderLine;

/**
 * DIBS_Requests_Items class.
 *
 * Class that formats the cart items sent to Nets.
 */
class Nets_Easy_Cart_Helper {

	/**
	 * Gets formatted cart items.
	 *
	 * @return array
	 */
	public static function get_items() {
		$items = array();

		// Get cart items.
		$cart_items = WC()->cart->get_cart_contents();
		foreach ( $cart_items as $cart_item ) {
			$items[] = self::get_item( $cart_item );
		}

		// Get coupons/gift cards.
		foreach ( Nets_Easy()->WC()->compatibility()->giftcards() as $giftcards ) {
			if ( false !== ( strpos( get_class( $giftcards ), 'WCGiftCards', true ) ) && ! function_exists( 'WC_GC' ) ) {
				continue;
			}

			$retrieved_giftcards = $giftcards->get_cart_giftcards();
			foreach ( $retrieved_giftcards as $retrieved_giftcard ) {
				$items[] = self::get_discount( $retrieved_giftcard );
			}
		}

		// Get cart fees.
		$cart_fees = WC()->cart->get_fees();
		foreach ( $cart_fees as $fee ) {
			$items[] = self::get_fees( $fee );
		}

		// Get cart shipping.
		if ( WC()->cart->needs_shipping() ) {
			$shipping = self::get_shipping();
			if ( null !== $shipping ) {
				$items[] = $shipping;
			}
		}

		return $items;
	}

	/**
	 * Gets one formatted cart line item.
	 *
	 * @param array $cart_item The WooCommerce cart line item.
	 * @return array
	 */
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
			'name'             => wc_dibs_clean_name( $product->get_name() ),
			'quantity'         => $cart_item['quantity'],
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
			'unitPrice'        => intval( round( ( $cart_item['line_total'] / $cart_item['quantity'] ) * 100 ) ),
			'taxRate'          => self::get_item_tax_rate( $cart_item, $product ),
			'taxAmount'        => intval( round( $cart_item['line_tax'] * 100, 2 ) ),
			'grossTotalAmount' => intval( round( ( $cart_item['line_total'] + $cart_item['line_tax'] ) * 100 ) ),
			'netTotalAmount'   => intval( round( $cart_item['line_total'] * 100 ) ),
		);
	}

	/**
	 * Gets a formatted discount item (e.g., coupon, gift card).
	 *
	 * @param OrderLine $item The WooCommerce order line item.
	 * @return array
	 */
	public static function get_discount( $item ) {
		return array(
			'reference'        => $item->get_sku(),
			'name'             => $item->get_name(),
			'quantity'         => 1,
			'unitPrice'        => $item->get_unit_price() + $item->get_unit_tax_amount(),
			'taxRate'          => 0,
			'grossTotalAmount' => $item->get_total_amount() + $item->get_total_tax_amount(),
			'netTotalAmount'   => $item->get_total_amount(),
			'taxAmount'        => 0,
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
		);
	}

	/**
	 * Gets one formatted cart fee item.
	 *
	 * @param object $fee The WooCommerce fee line item.
	 * @return array
	 */
	public static function get_fees( $fee ) {
		return array(
			'reference'        => 'fee|' . $fee->id,
			'name'             => wc_dibs_clean_name( $fee->name ),
			'quantity'         => 1,
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
			'unitPrice'        => intval( round( $fee->amount * 100 ) ),
			'taxRate'          => intval( round( ( $fee->tax / $fee->amount ) * 10000, 2 ) ),
			'taxAmount'        => intval( round( $fee->tax * 100, 2 ) ),
			'grossTotalAmount' => intval( round( ( $fee->amount + $fee->tax ) * 100 ) ),
			'netTotalAmount'   => intval( round( $fee->amount * 100 ) ),
		);
	}

	/**
	 * Gets one formatted cart shipping item.
	 *
	 * @return array|null
	 */
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
							'reference'        => 'shipping|' . $method->id,
							'name'             => wc_dibs_clean_name( $method->label ),
							'quantity'         => 1,
							'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
							'unitPrice'        => intval( round( $method->cost * 100 ) ),
							'taxRate'          => intval( round( ( array_sum( $method->taxes ) / $method->cost ) * 10000, 2 ) ),
							'taxAmount'        => intval( round( array_sum( $method->taxes ) * 100, 2 ) ),
							'grossTotalAmount' => intval( round( ( $method->cost + array_sum( $method->taxes ) ) * 100, 2 ) ),
							'netTotalAmount'   => intval( round( $method->cost * 100 ) ),
						);
					}

					return array(
						'reference'        => 'shipping|' . $method->id,
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

		return null;
	}

	/**
	 * Gets the sku for one item.
	 *
	 * @param object $product The WooCommerce product.
	 * @param string $product_id The WooCommerce product ID.
	 * @return string
	 */
	public static function get_sku( $product, $product_id ) {
		$part_number = $product->get_sku();
		if ( empty( $part_number ) ) {
			$part_number = $product->get_id();
		}
		return substr( $part_number, 0, 32 );
	}

	/**
	 * Calculate item tax percentage.
	 *
	 * @since  1.8.2
	 *
	 * @param  array  $cart_item Cart item.
	 * @param  object $product   Product object.
	 *
	 * @return integer $item_tax_rate Item tax percentage formatted for DIBS.
	 */
	public static function get_item_tax_rate( $cart_item, $product ) {
		if ( $product->is_taxable() && $cart_item['line_subtotal_tax'] > 0 ) {
			// Calculate tax rate.
			$_tax      = new WC_Tax();
			$tmp_rates = $_tax->get_rates( $product->get_tax_class() );
			$vat       = array_shift( $tmp_rates );
			if ( isset( $vat['rate'] ) ) {
				$item_tax_rate = round( $vat['rate'] * 100 );
			} else {
				$item_tax_rate = 0;
			}
		} else {
			$item_tax_rate = 0;
		}
		return round( $item_tax_rate );
	}
}
