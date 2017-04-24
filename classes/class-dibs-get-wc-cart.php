<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Get_WC_Cart {
	function __construct() {
	}

	// Create the datastring for the AJAX call
	public function createCart($orderID){
		global $woocommerce;
		$wc_cart = $woocommerce->cart->cart_contents;
		// Set arrays
		$cart = array();
		$order = array();
		$items = array();
		// Create the items objects for each product in cart
		foreach($wc_cart as $item){
			$item_name = wc_get_product( $item['product_id'] );
			$item_name = $item_name->get_title();
			$itemLine = $this->createItems($item_name, $item['quantity'], $item['line_subtotal'], $item['line_subtotal_tax']);
			array_push($items, $itemLine);
		}
		// Add shipping as an item for order.
		$shipping = $this->shippingCost();
		if($shipping != '') {
			array_push( $items, $shipping );
		}

		// Set the rest of the order array objects
		$amount = $this->getTotalAmount($items);
		$currency = get_woocommerce_currency();
		$reference = $orderID;

		// Create the order array
		$order['items']     = $items;
		$order['amount']    = $amount;
		$order['currency']  = $currency;
		$order['reference'] = $reference;

		//Get the checkout URL from the options.
		$gateway = new DIBS_Easy_Gateway();
		$checkout['url'] = $gateway->checkout_url;

		// Create the final cart array for the datastring
		$cart['order'] = $order;
		$cart['checkout'] = $checkout;
		return json_encode($cart, JSON_UNESCAPED_SLASHES);
	}

	// Get order information from order ID
	public function getOrderCart($orderID){
		// Get the order from orderID and get the items
		$order = wc_get_order( $orderID );
		$order_shipping = $order->get_items( 'shipping' );
		$order_item = $order->get_items();

		$items = array();
		// Get the items from the array and save in a format that works for DIBS
		foreach( $order_item as $item ) {
			$itemLine = $this->createItems($item['name'], $item['quantity'], $item['subtotal'], $item['subtotal_tax']);
			array_push($items, $itemLine);
		}
		foreach( $order_shipping as $shipping ){
			$shippingLine = $this->createItems($shipping['method_title'], 1, $shipping['total'], $shipping['total_tax']);
			array_push($items, $shippingLine);
		}
		// Calculate total amount to charge customer
		$amount = $this->getTotalAmount($items);

		$return = array();
		$return['amount'] = $amount;
		$return['orderItems'] = $items;
		return json_encode($return);
	}

	//Calculate and return shipping cost
	public function shippingCost(){
		if ( WC()->cart->needs_shipping() ) {
			WC()->cart->calculate_shipping();
			$packages = WC()->shipping->get_packages();
			$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
			$chosen_shipping = $chosen_methods[0];
			foreach ( $packages as $i => $package ) {
				foreach ( $package['rates'] as $method ) {
					if ( $chosen_shipping === $method->id ) {
						if($method->cost > 0 ) {
							$shippingItem = $this->createItems($method->label, 1, $method->cost, array_sum( $method->taxes ));
							return $shippingItem;
						}
					}
				}
			}
		}
	}

	// Create the item array objects.
	public function createItems($item_name, $item_quanitity, $item_line_subtotal, $item_line_subtotal_tax){
		// Set the different variables for the item object
		$reference        = '1';
		$name             = $item_name;
		$quantity         = $item_quanitity;
		$unit             = (string)$item_quanitity;
		$unitPrice        = round( ( $item_line_subtotal * 100 ) / $unit );
		$taxRate          = ($item_line_subtotal_tax / $item_line_subtotal) * 10000;
		$taxAmount        = $item_line_subtotal_tax * 100;
		$grossTotalAmount = round( ( $item_line_subtotal + $item_line_subtotal_tax ) * 100 );
		$netTotalAmount   = $item_line_subtotal * 100;

		// Return the item object array
		return array(
			"reference"         => $reference,
			"name"              => $name,
			"quantity"          => $quantity,
			"unit"              => $unit,
			"unitPrice"         => $unitPrice,
			"taxRate"           => $taxRate,
			"taxAmount"         => $taxAmount,
			"grossTotalAmount"  => $grossTotalAmount,
			"netTotalAmount"    => $netTotalAmount
		);
	}

	// Calculate the total cart value
	public function getTotalAmount($items){
		$amount = 0;
		foreach ($items as $item){
			foreach($item as $key => $value) {
				if ( $key == 'grossTotalAmount' ) {
					$value = intval($value);
					$amount = $amount + $value;
				}
			}
		}
		return $amount;
	}
}
$dibs_get_wc_cart = new DIBS_Get_WC_Cart();