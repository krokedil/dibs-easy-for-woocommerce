<?php
/**
 * Formats the cart items sent to Nets.
 *
 * @package DIBS_Easy/Classes/Requests/Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * DIBS_Requests_Items class.
 *
 * Class that formats the cart items sent to Nets.
 */
class DIBS_Requests_Items {

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

		// YITH Gift Cards.
		if ( ! empty( WC()->cart->applied_gift_cards ) ) {
			foreach ( WC()->cart->applied_gift_cards as $coupon_key => $code ) {
				$coupon_amount = isset( WC()->cart->applied_gift_cards_amounts[ $code ] ) ? - WC()->cart->applied_gift_cards_amounts[ $code ] * 100 : 0;
				$label         = apply_filters( 'yith_ywgc_cart_totals_gift_card_label', esc_html( __( 'Gift card:', 'yith-woocommerce-gift-cards' ) . ' ' . $code ), $code );
				$giftcard_sku  = apply_filters( 'nets_yith_gift_card_sku', esc_html( __( 'giftcard', 'dibs-easy-for-woocommerce' ) ), $code );

				$gift_card = array(
					'reference'        => $giftcard_sku,
					'name'             => $label,
					'quantity'         => 1,
					'unitPrice'        => $coupon_amount,
					'taxRate'          => 0,
					'grossTotalAmount' => $coupon_amount,
					'netTotalAmount'   => $coupon_amount,
					'taxAmount'        => 0,
					'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
				);
			}
			$items[] = $gift_card;
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
	 * @return array
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
					} else {
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
		}
	}

	/**
	 * Gets the sku for one item.
	 *
	 * @param object $product The WooCommerce product.
	 * @param string $product_id The WooCommerce product ID.
	 * @return string
	 */
	public static function get_sku( $product, $product_id ) {
		if ( get_post_meta( $product_id, '_sku', true ) !== '' ) {
			$part_number = $product->get_sku();
		} else {
			$part_number = $product->get_id();
		}
		return substr( $part_number, 0, 32 );
	}

	/**
	 * Calculate item tax percentage.
	 *
	 * @since  1.8.2
	 * @access public
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
