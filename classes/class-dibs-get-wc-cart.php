<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Get_WC_Cart {
	function __construct() {
	}

	// Create the datastring for the AJAX call
	public function create_cart( $order_id ) {
		$wc_cart = WC()->cart->cart_contents;
		// Set arrays
		$cart  = array();
		$order = array();
		$items = array();
		// Create the items objects for each product in cart
		foreach ( $wc_cart as $item ) {
			$item_name = wc_get_product( $item['product_id'] );
			$item_name = $item_name->get_title();
			$item_line = $this->create_items( $item['product_id'], $item_name, $item['quantity'], $item['line_total'], $item['line_tax'] );
			array_push( $items, $item_line );
		}
		// Add shipping as an item for order.
		$shipping = $this->shipping_cost();
		if ( '' != $shipping ) {
			array_push( $items, $shipping );
		}
		// Set the rest of the order array objects
		$amount = $this->get_total_amount( $items );
		$currency = get_woocommerce_currency();
		$wc_order = wc_get_order( $order_id );
		// Make sure to run Sequential Order numbers if plugin exsists
		if ( class_exists( 'WC_Seq_Order_Number_Pro' ) ) {
			$sequential = new WC_Seq_Order_Number_Pro;
			$sequential->set_sequential_order_number( $order_id );
			$reference = $sequential->get_order_number( $wc_order->get_order_number(), $wc_order );
		} elseif ( class_exists( 'WC_Seq_Order_Number' ) ) {
			$sequential = new WC_Seq_Order_Number;
			$sequential->set_sequential_order_number( $order_id, get_post( $order_id ) );
			$reference = $sequential->get_order_number( $wc_order->get_order_number(), $wc_order );
		} else {
			$reference = $wc_order->get_order_number();
		}
		error_log( '$order_id' . $order_id );
		error_log( '$reference' . $reference );

		// Create the order array
		$order['items']     = $items;
		$order['amount']    = $amount;
		$order['currency']  = $currency;
		$order['reference'] = $reference;

		//Get the checkout URL
		$checkout['url'] = wc_get_checkout_url();

		// Create the final cart array for the datastring
		$cart['order'] = $order;
		$cart['checkout'] = $checkout;
		return $cart;
	}

	// Get order information from order ID
	public function get_order_cart( $order_id ) {
		// Get the order from orderID and get the items
		$order = wc_get_order( $order_id );
		$order_shipping = $order->get_items( 'shipping' );
		$order_item = $order->get_items();

		$items = array();
		// Get the items from the array and save in a format that works for DIBS
		foreach ( $order_item as $item ) {
			$item_line = $this->create_items( $item['product_id'], $item['name'], $item['quantity'], $item['total'], $item['total_tax'] );
			array_push( $items, $item_line );
		}
		foreach ( $order_shipping as $shipping ) {
			if ( $shipping['total'] > 0 ) {
				$shipping_line = $this->create_items( '1', $shipping['method_title'], 1, $shipping['total'], $shipping['total_tax'] );
				array_push( $items, $shipping_line );
			}
		}
		// Calculate total amount to charge customer
		$amount = $this->get_total_amount( $items );

		$return = array();
		$return['amount'] = $amount;
		$return['orderItems'] = $items;
		return $return;
	}

	// Calculate and return shipping cost
	public function shipping_cost() {
		if ( WC()->cart->needs_shipping() ) {
			WC()->cart->calculate_shipping();
			$packages = WC()->shipping->get_packages();
			$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
			$chosen_shipping = $chosen_methods[0];
			foreach ( $packages as $i => $package ) {
				foreach ( $package['rates'] as $method ) {
					if ( $chosen_shipping === $method->id ) {
						if ( $method->cost > 0 ) {
							$shipping_item = $this->create_items( '1', $method->label, 1, $method->cost, array_sum( $method->taxes ) );
							return $shipping_item;
						}
					}
				}
			}
		}
	}

	// Create the item array objects.
	public function create_items( $item_sku, $item_name, $item_quantity, $item_line_total, $item_line_tax ) {
		// Set the different variables for the item object
		$reference        = $item_sku;
		$name             = $item_name;
		$quantity         = $item_quantity;
		$unit             = (string) $item_quantity;
		$unit_price       = round( ( $item_line_total * 100 ) / $unit );
		$tax_rate         = round( ( $item_line_tax / $item_line_total ) * 10000 );
		$tax_amount       = $item_line_tax * 100;
		$gross_total_amount = round( ( $item_line_total + $item_line_tax ) * 100 );
		$net_total_amount   = round( $item_line_total * 100 );

		// Return the item object array
		return array(
			'reference'         => $reference,
			'name'              => $name,
			'quantity'          => $quantity,
			'unit'              => __( 'pcs', 'woocommerce-dibs-easy' ),
			'unitPrice'         => $unit_price,
			'taxRate'           => $tax_rate,
			'taxAmount'         => $tax_amount,
			'grossTotalAmount'  => $gross_total_amount,
			'netTotalAmount'    => $net_total_amount,
		);
	}

	// Calculate the total cart value
	public function get_total_amount( $items ) {
		$amount = 0;
		foreach ( $items as $item ) {
			foreach ( $item as $key => $value ) {
				if ( 'grossTotalAmount' == $key ) {
					$value = intval( $value );
					$amount = $amount + $value;
				}
			}
		}
		return $amount;
	}
	public function get_sku( $product ) {
		if ( $product->get_sku() ) {
			$part_number = $product->get_sku();
		} elseif ( $product->variation_id ) {
			$part_number = $product->variation_id;
		} else {
			$part_number = $product->id;
		}
		return substr( $part_number, 0, 32 );
	}
}
$dibs_get_wc_cart = new DIBS_Get_WC_Cart();
