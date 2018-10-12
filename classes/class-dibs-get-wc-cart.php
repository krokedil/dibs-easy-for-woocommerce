<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Get_WC_Cart {
	function __construct() {
	}

	// Create the datastring for the AJAX call
	public function create_cart( $order_id ) {
		$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		$wc_cart       = WC()->cart->cart_contents;
		// Set arrays
		$cart  = array();
		$order = array();
		$items = array();
		// Create the items objects for each product in cart
		foreach ( $wc_cart as $item ) {
			$item_name = wc_get_product( $item['product_id'] );
			$item_name = $item_name->get_title();
			if ( $item['variation_id'] ) {
				$product    = wc_get_product( $item['variation_id'] );
				$product_id = $item['variation_id'];
			} else {
				$product    = wc_get_product( $item['product_id'] );
				$product_id = $item['product_id'];
			}
			$item_line = $this->create_items( $this->get_sku( $product, $product_id ), $item_name, $item['quantity'], $item['line_total'], $item['line_tax'] );
			array_push( $items, $item_line );
		}
		// Add shipping as an item for order.
		$shipping = $this->shipping_cost();
		if ( '' != $shipping ) {
			array_push( $items, $shipping );
		}

		// Fees
		if ( ! empty( WC()->cart->get_fees() ) ) {
			foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {
				$fee_line = $this->create_items( $fee->id, $fee->name, 1, $fee->amount, $fee->tax );
				array_push( $items, $fee_line );
			}
		}

		// Set the rest of the order array objects
		$amount   = $this->get_total_amount( $items );
		$currency = get_woocommerce_currency();
		$wc_order = wc_get_order( $order_id );
		// Make sure to run Sequential Order numbers if plugin exsists
		if ( class_exists( 'WC_Seq_Order_Number_Pro' ) ) {
			$sequential = new WC_Seq_Order_Number_Pro();
			$sequential->set_sequential_order_number( $order_id );
			$reference = $sequential->get_order_number( $wc_order->get_order_number(), $wc_order );
		} elseif ( class_exists( 'WC_Seq_Order_Number' ) ) {
			$sequential = new WC_Seq_Order_Number();
			$sequential->set_sequential_order_number( $order_id, get_post( $order_id ) );
			$reference = $sequential->get_order_number( $wc_order->get_order_number(), $wc_order );
		} else {
			$reference = $wc_order->get_order_number();
		}

		// Create the order array
		$order['items']                     = $items;
		$order['amount']                    = $amount;
		$order['currency']                  = $currency;
		$order['reference']                 = $reference;
		$order['shipping']['costSpecified'] = true;

		// Get the checkout URL
		$checkout['url'] = wc_get_checkout_url();

		// Get the terms URL
		$checkout['termsUrl'] = wc_get_page_permalink( 'terms' );

		// Get available shipping countries
		if ( 'all' !== get_option( 'woocommerce_allowed_countries' ) ) {
			$checkout['ShippingCountries'] = $this->get_shipping_countries();
		}

		// Test
		$checkout['shipping']['countries']                   = array();
		$checkout['shipping']['merchantHandlesShippingCost'] = true;

		// Get consumerType
		$allowed_customer_types = ( isset( $dibs_settings['allowed_customer_types'] ) ) ? $dibs_settings['allowed_customer_types'] : 'B2C';
		switch ( $allowed_customer_types ) {
			case 'B2C':
				$checkout['consumerType']['supportedTypes'] = array( 'B2C' );
				break;
			case 'B2B':
				$checkout['consumerType']['supportedTypes'] = array( 'B2B' );
				break;
			case 'B2CB':
				$checkout['consumerType']['supportedTypes'] = array( 'B2C', 'B2B' );
				$checkout['consumerType']['default']        = 'B2C';
				break;
			case 'B2BC':
				$checkout['consumerType']['supportedTypes'] = array( 'B2B', 'B2C' );
				$checkout['consumerType']['default']        = 'B2B';
				break;
			default:
				$checkout['consumerType']['supportedTypes'] = array( 'B2B' );
		} // End switch().

		// Create the final cart array for the datastring
		$cart['order']                     = $order;
		$cart['checkout']                  = $checkout;
		$cart['notifications']['webHooks'] = $this->get_web_hooks();
		return $cart;
	}

	// Create the datastring for the AJAX call
	public function update_cart( $order_id ) {
		$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		$wc_cart       = WC()->cart->cart_contents;
		// Set arrays
		$cart = array();

		$items = array();
		// Create the items objects for each product in cart
		foreach ( $wc_cart as $item ) {
			$item_name = wc_get_product( $item['product_id'] );
			$item_name = $item_name->get_title();
			if ( $item['variation_id'] ) {
				$product    = wc_get_product( $item['variation_id'] );
				$product_id = $item['variation_id'];
			} else {
				$product    = wc_get_product( $item['product_id'] );
				$product_id = $item['product_id'];
			}
			$item_line = $this->create_items( $this->get_sku( $product, $product_id ), $item_name, $item['quantity'], $item['line_total'], $item['line_tax'] );
			array_push( $items, $item_line );
		}
		// Add shipping as an item for order.
		$shipping = $this->shipping_cost();
		if ( '' != $shipping ) {
			array_push( $items, $shipping );
		}

		// Fees
		if ( ! empty( WC()->cart->get_fees() ) ) {
			foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {
				$fee_line = $this->create_items( $fee->id, $fee->name, 1, $fee->amount, $fee->tax );
				array_push( $items, $fee_line );
			}
		}

		// Set the rest of the order array objects
		$amount   = $this->get_total_amount( $items );
		$wc_order = wc_get_order( $order_id );

		// Create the order array
		$cart['amount']                    = $amount;
		$cart['items']                     = $items;
		$cart['shipping']['costSpecified'] = true;
		return $cart;
	}

	// Get order information from order ID
	public function get_order_cart( $order_id ) {
		// Get the order from orderID and get the items
		$order          = wc_get_order( $order_id );
		$order_shipping = $order->get_items( 'shipping' );
		$order_item     = $order->get_items();

		$items = array();
		// Get the items from the array and save in a format that works for DIBS
		foreach ( $order_item as $item ) {
			if ( $item['variation_id'] ) {
				$product    = wc_get_product( $item['variation_id'] );
				$product_id = $item['variation_id'];
			} else {
				$product    = wc_get_product( $item['product_id'] );
				$product_id = $item['product_id'];
			}
			$item_line = $this->create_items( $this->get_sku( $product, $product_id ), $item['name'], $item['quantity'], $item['total'], $item['total_tax'] );
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

		$return               = array();
		$return['amount']     = $amount;
		$return['orderItems'] = $items;
		return $return;
	}

	// Calculate and return shipping cost
	public function shipping_cost() {
		if ( WC()->cart->needs_shipping() ) {
			WC()->cart->calculate_shipping();
			$packages        = WC()->shipping->get_packages();
			$chosen_methods  = WC()->session->get( 'chosen_shipping_methods' );
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
		$reference          = $item_sku;
		$name               = $item_name;
		$quantity           = $item_quantity;
		$unit               = (string) $item_quantity;
		$unit_price         = round( ( $item_line_total * 100 ) / $unit );
		$tax_rate           = round( ( $item_line_tax / $item_line_total ) * 10000 );
		$tax_amount         = intval( round( $item_line_tax, 2 ) * 100 );
		$gross_total_amount = round( ( $item_line_total + $item_line_tax ) * 100 );
		$net_total_amount   = round( $item_line_total * 100 );

		// Clean the name of illegal characters
		$name = preg_replace( '/[^!#$%()*+,-.\/:;=?@\[\]\\\^_`{}|~a-zA-Z0-9\s]+/i', '', $name );

		// Return the item object array
		return array(
			'reference'        => $reference,
			'name'             => $name,
			'quantity'         => $quantity,
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
			'unitPrice'        => $unit_price,
			'taxRate'          => $tax_rate,
			'taxAmount'        => $tax_amount,
			'grossTotalAmount' => $gross_total_amount,
			'netTotalAmount'   => $net_total_amount,
		);
	}

	// Calculate the total cart value
	public function get_total_amount( $items ) {
		$amount = 0;
		foreach ( $items as $item ) {
			foreach ( $item as $key => $value ) {
				if ( 'grossTotalAmount' == $key ) {
					$value  = intval( $value );
					$amount = $amount + $value;
				}
			}
		}
		return $amount;
	}
	public function get_sku( $product, $product_id ) {
		if ( get_post_meta( $product_id, '_sku', true ) !== '' ) {
			$part_number = $product->get_sku();
		} else {
			$part_number = $product->get_id();
		}
		return substr( $part_number, 0, 32 );
	}

	public function get_shipping_countries() {
		$converted_countries      = array();
		$supported_dibs_countries = dibs_get_supported_countries();
		// Add shipping countries
		$wc_countries = new WC_Countries();
		$countries    = array_keys( $wc_countries->get_allowed_countries() );
		$i            = 0;
		foreach ( $countries as $country ) {
			$converted_country = dibs_get_iso_3_country( $country );
			if ( in_array( $converted_country, $supported_dibs_countries ) ) {
				$converted_countries[] = array( 'countryCode' => $converted_country );
				$i++;
				// DIBS only allow 5 countries
				if ( $i == 5 ) {
					break;
				}
			}
		}
		return $converted_countries;
	}

	// Prepare webhooks
	public function get_web_hooks() {
		$web_hooks = array();
		/*
		$web_hooks[] = array(
			'eventName'     => 'payment.reservation.created',
			'url'           => get_home_url() . '/wc-api/DIBS_WC_Payment_Created/',
			'authorization' => wp_create_nonce( 'dibs_web_hooks' ),
		);*/

		return $web_hooks;
	}
}
$dibs_get_wc_cart = new DIBS_Get_WC_Cart();
