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
	public static function get_order( $checkout_flow = 'inline', $order_id = null ) {
		if ( nexi_is_embedded( $checkout_flow ) ) {
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

		$items  = Nets_Easy_Order_Items_Helper::get_items( $order_id );
		$order  = wc_get_order( $order_id );
		$amount = intval( round( $order->get_total() * 100, 2 ) );

		return array(
			'items'     => self::reconcile_items( $items, $amount ),
			'amount'    => $amount,
			'currency'  => $order->get_currency(),
			'reference' => $order->get_order_number(),
		);
	}

	/**
	 * Makes the sum of the order lines match the order total.
	 *
	 * Every line is rounded to minor units on its own, while Woo rounds the order
	 * total only once, so the sum of the lines can drift a few minor units from the
	 * total. Nets rejects any payload where the two disagree, so the difference is
	 * folded into the largest line.
	 *
	 * @param array $items  The order line items, with amounts already in minor units.
	 * @param int   $amount The order total in minor units.
	 *
	 * @return array
	 */
	public static function reconcile_items( $items, $amount ) {
		$line_total = self::get_order_total( $items );
		$diff       = intval( $amount ) - $line_total;

		if ( 0 === $diff ) {
			return $items;
		}

		// Rounding each line on its own can lose at most one minor unit per line, so
		// anything larger has a different cause and needs to be visible in the log.
		if ( abs( $diff ) > max( 1, count( $items ) ) ) {
			Nets_Easy_Logger::log( 'Order line total differs from the order total by more than line rounding can account for. Order total: ' . $amount . '. Sum of order lines: ' . $line_total . '. Difference: ' . $diff . '.' );
		}

		$key = self::get_reconcilable_item( $items, $diff );

		if ( null === $key ) {
			return $items;
		}

		$items[ $key ]['grossTotalAmount'] += $diff;

		// Keep grossTotalAmount equal to netTotalAmount plus taxAmount. The difference
		// stems from tax rounding, so tax absorbs it unless the line is untaxed.
		if ( 0 !== intval( $items[ $key ]['taxAmount'] ) ) {
			$items[ $key ]['taxAmount'] += $diff;
		} else {
			$items[ $key ]['netTotalAmount'] += $diff;
		}

		return $items;
	}

	/**
	 * Gets the key of the order line best suited to absorb a rounding difference.
	 *
	 * Prefers the largest taxed line, since the difference stems from tax rounding,
	 * and falls back to the largest line of any kind. Lines that would be left with a
	 * negative amount are skipped.
	 *
	 * @param array $items The order line items, with amounts already in minor units.
	 * @param int   $diff  The difference to absorb, in minor units.
	 *
	 * @return int|string|null The key of the line, or null if no line can absorb the difference.
	 */
	private static function get_reconcilable_item( $items, $diff ) {
		$taxed    = null;
		$fallback = null;

		foreach ( $items as $key => $item ) {
			$gross = intval( $item['grossTotalAmount'] ?? 0 );
			$net   = intval( $item['netTotalAmount'] ?? 0 );
			$tax   = intval( $item['taxAmount'] ?? 0 );

			// The difference lands on the gross amount and on either the tax or the net amount.
			$absorbed = ( 0 !== $tax ) ? $tax : $net;
			if ( $gross + $diff < 0 || $absorbed + $diff < 0 ) {
				continue;
			}

			if ( null === $fallback || $gross > intval( $items[ $fallback ]['grossTotalAmount'] ) ) {
				$fallback = $key;
			}

			if ( 0 !== $tax && ( null === $taxed || $gross > intval( $items[ $taxed ]['grossTotalAmount'] ) ) ) {
				$taxed = $key;
			}
		}

		return $taxed ?? $fallback;
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
