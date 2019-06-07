<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Create_Local_Order_Fallback {
	public function create_order() {
		$order = wc_create_order();
		return $order;
	}
	public function add_items_to_local_order( $order ) {
			// Remove items as to stop the item lines from being duplicated.
			$order->remove_order_items();
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) { // Store the line items to the new/resumed order.
			$item_id = $order->add_product(
				$values['data'],
				$values['quantity'],
				array(
					'variation' => $values['variation'],
					'totals'    => array(
						'subtotal'     => $values['line_subtotal'],
						'subtotal_tax' => $values['line_subtotal_tax'],
						'total'        => $values['line_total'],
						'tax'          => $values['line_tax'],
						'tax_data'     => $values['line_tax_data'],
					),
				)
			);
			if ( ! $item_id ) {
				DIBS_Easy::log( 'Error: Unable to add cart items in Create Local Order Fallback.' );
				throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 525 ) );
			}
			do_action( 'woocommerce_add_order_item_meta', $item_id, $values, $cart_item_key ); // Allow plugins to add order item meta.
		}
	}

	public function add_order_fees( $order ) {
			$order_id = $order->get_id();
		foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {
			$item_id = $order->add_fee( $fee );
			if ( ! $item_id ) {
				DIBS_Easy::log( 'Error: Unable to add order fees in Create Local Order Fallback.' );
				throw new Exception( __( 'Error: Unable to create order. Please try again.', 'woocommerce' ) );
			}
			// Allow plugins to add order item meta to fees.
			do_action( 'woocommerce_add_order_fee_meta', $order_id, $item_id, $fee, $fee_key );
		}
	}


	/**
	 * Add shipping lines to the order.
	 *
	 * @param WC_Order $order                   Order Instance.
	 * @param array    $chosen_shipping_methods Chosen shipping methods.
	 * @param array    $packages                Packages.
	 */
	public function add_order_shipping( $order ) {
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
		$packages                = WC()->shipping->get_packages();
		foreach ( $packages as $package_key => $package ) {
			if ( isset( $chosen_shipping_methods[ $package_key ], $package['rates'][ $chosen_shipping_methods[ $package_key ] ] ) ) {
				$shipping_rate            = $package['rates'][ $chosen_shipping_methods[ $package_key ] ];
				$item                     = new WC_Order_Item_Shipping();
				$item->legacy_package_key = $package_key; // @deprecated For legacy actions.
				$item->set_props(
					array(
						'method_title' => $shipping_rate->label,
						'method_id'    => $shipping_rate->method_id,
						'instance_id'  => $shipping_rate->instance_id,
						'total'        => wc_format_decimal( $shipping_rate->cost ),
						'taxes'        => array(
							'total' => $shipping_rate->taxes,
						),
					)
				);
				foreach ( $shipping_rate->get_meta_data() as $key => $value ) {
					$item->add_meta_data( $key, $value, true );
				}
				/**
				 * Action hook to adjust item before save.
				 *
				 * @since 3.0.0
				 */
				do_action( 'woocommerce_checkout_create_order_shipping_item', $item, $package_key, $package, $order );
				// Add item to order and save.
				$order->add_item( $item );
			}
		}
	}


	public function add_order_tax_rows( $order ) {
		// Store tax rows.
		foreach ( array_keys( WC()->cart->taxes + WC()->cart->shipping_taxes ) as $tax_rate_id ) {
			if ( $tax_rate_id && ! $order->add_tax( $tax_rate_id, WC()->cart->get_tax_amount( $tax_rate_id ), WC()->cart->get_shipping_tax_amount( $tax_rate_id ) ) && apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) !== $tax_rate_id ) {
				DIBS_Easy::log( 'Error: Unable to add order tax rows in Create Local Order Fallback.' );
				throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 405 ) );
			}
		}
	}

	public function add_order_coupons( $order ) {
		foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
			if ( ! $order->add_coupon( $code, WC()->cart->get_coupon_discount_amount( $code ) ) ) {
				DIBS_Easy::log( 'Error: Unable to add coupons in Create Local Order Fallback.' );
				throw new Exception( __( 'Error: Unable to add coupons. Please try again.', 'woocommerce' ) );
			}
		}
	}
	public function add_order_payment_method( $order ) {
		$available_gateways = WC()->payment_gateways->payment_gateways();
		$payment_method     = $available_gateways['dibs_easy'];
		$order->set_payment_method( $payment_method );
	}
	public function add_customer_data_to_local_order( $order, $payment_id ) {
		$order_id      = $order->get_id();
		$customer_data = array();

		$dibs_order = new DIBS_Requests_Get_DIBS_Order( $payment_id, $order_id );
		$dibs_order = $dibs_order->request();

		if ( array_key_exists( 'name', $dibs_order->payment->consumer->company ) ) {
			$type     = 'company';
			$customer = $dibs_order->payment->consumer->company;
		} else {
			$type     = 'person';
			$customer = $dibs_order->payment->consumer->privatePerson;
		}

		update_post_meta( $order_id, '_billing_first_name', ( 'person' === $type ) ? $customer->firstName : $customer->contactDetails->firstName );
		update_post_meta( $order_id, '_billing_last_name', ( 'person' === $type ) ? $customer->lastName : $customer->contactDetails->lastName );
		update_post_meta( $order_id, '_billing_address_1', $dibs_order->payment->consumer->shippingAddress->addressLine1 );
		update_post_meta( $order_id, '_billing_city', $dibs_order->payment->consumer->shippingAddress->city );
		update_post_meta( $order_id, '_billing_postcode', $dibs_order->payment->consumer->shippingAddress->postalCode );
		update_post_meta( $order_id, '_billing_country', dibs_get_iso_2_country( $dibs_order->payment->consumer->shippingAddress->country ) );
		update_post_meta( $order_id, '_billing_phone', ( 'person' === $type ) ? $customer->phoneNumber->number : $customer->contactDetails->phoneNumber->number );
		update_post_meta( $order_id, '_billing_email', ( 'person' === $type ) ? $customer->email : $customer->contactDetails->email );
		update_post_meta( $order_id, '_shipping_first_name', ( 'person' === $type ) ? $customer->firstName : $customer->contactDetails->firstName );
		update_post_meta( $order_id, '_shipping_last_name', ( 'person' === $type ) ? $customer->lastName : $customer->contactDetails->firstName );
		update_post_meta( $order_id, '_shipping_address_1', $dibs_order->payment->consumer->shippingAddress->addressLine1 );
		update_post_meta( $order_id, '_shipping_city', $dibs_order->payment->consumer->shippingAddress->city );
		update_post_meta( $order_id, '_shipping_postcode', $dibs_order->payment->consumer->shippingAddress->postalCode );
		update_post_meta( $order_id, '_shipping_country', dibs_get_iso_2_country( $dibs_order->payment->consumer->shippingAddress->country ) );

		if ( 'company' === $type ) {
			update_post_meta( $order_id, '_billing_company', $customer->name );
			update_post_meta( $order_id, '_shipping_company', $customer->name );
		}

		if ( isset( $dibs_order->payment->consumer->shippingAddress->addressLine2 ) ) {
			update_post_meta( $order_id, '_billing_address_2', $dibs_order->payment->consumer->shippingAddress->addressLine2 );
			update_post_meta( $order_id, '_shipping_address_2', $dibs_order->payment->consumer->shippingAddress->addressLine2 );
		}

		update_post_meta( $order_id, '_created_via_dibs_fallback', 'yes' );

		$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() ) );
	}
	public function calculate_order_totals( $order ) {
		$order->calculate_totals();
		$order->save();
	}

	// Update the DIBS Order with the Order ID
	public function update_order_reference_in_dibs( $order_number ) {
		$request = new DIBS_Requests_Update_DIBS_Order_Reference( $payment_id, $order_number );
		$request->request();
	}
}
