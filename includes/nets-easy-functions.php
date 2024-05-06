<?php
/**
 * Nets checkout functions
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maybe create an order.
 *
 * @return array|mixed|void|WP_Error
 */
function dibs_easy_maybe_create_order() {
	$cart       = WC()->cart;
	$session    = WC()->session;
	$payment_id = WC()->session->get( 'dibs_payment_id' );
	$cart->calculate_fees();
	$cart->calculate_shipping();
	$cart->calculate_totals();
	if ( $payment_id ) {
		$dibs_easy_order = Nets_Easy()->api->get_nets_easy_order( $payment_id );
		if ( is_wp_error( $dibs_easy_order ) ) {
			$session->__unset( 'dibs_payment_id' );

			return dibs_easy_maybe_create_order();
		}

		return $dibs_easy_order;
	}
	// create the order.
	$dibs_easy_order = Nets_Easy()->api->create_nets_easy_order();
	if ( is_wp_error( $dibs_easy_order ) || ! $dibs_easy_order['paymentId'] ) {
		// If failed then bail.
		return;
	}
	// store payment id.
	$session->set( 'dibs_payment_id', $dibs_easy_order['paymentId'] );
	$session->set( 'nets_easy_currency', get_woocommerce_currency() );
	$session->set( 'nets_easy_last_update_hash', $cart->get_cart_hash() );
	$session->set( 'dibs_cart_contains_subscription', get_dibs_cart_contains_subscription() );
	// Set a transient for this paymentId. It's valid in DIBS system for 20 minutes.
	$payment_id = $dibs_easy_order['paymentId'];
	set_transient( 'dibs_payment_id_' . $payment_id, $payment_id, 15 * MINUTE_IN_SECONDS ); // phpcs:ignore

	// get dibs easy order.
	return $dibs_easy_order;
}

/**
 * Return string(yes) if cart contains subscription product
 *
 * @return string
 */
function get_dibs_cart_contains_subscription() {
	if ( ( class_exists( 'WC_Subscriptions_Cart' ) && ( WC_Subscriptions_Cart::cart_contains_subscription() || wcs_cart_contains_renewal() ) ) ) {
		return 'yes';
	}
	return 'no';
}

/**
 * Shows select another payment method button in DIBS Checkout page.
 */
function wc_dibs_show_another_gateway_button() {
	$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

	if ( count( $available_gateways ) > 1 ) {
		$settings                   = get_option( 'woocommerce_dibs_easy_settings' );
		$select_another_method_text = isset( $settings['select_another_method_text'] ) && '' !== $settings['select_another_method_text'] ? $settings['select_another_method_text'] : __( 'Select another payment method', 'dibs-easy-for-woocommerce' );

		?>
		<p style="margin-top:30px">
			<a class="checkout-button button" href="#" id="dibs-easy-select-other">
				<?php echo esc_html( $select_another_method_text ); ?>
			</a>
		</p>
		<?php
	}
}

/**
 * Calculates cart totals.
 */
function wc_dibs_calculate_totals() {
	WC()->cart->calculate_fees();
	WC()->cart->calculate_shipping();
	WC()->cart->calculate_totals();
}

/**
 * Unset DIBS session
 */
function wc_dibs_unset_sessions() {

	if ( method_exists( WC()->session, '__unset' ) ) {
		if ( WC()->session->get( 'dibs_incomplete_order' ) ) {
			WC()->session->__unset( 'dibs_incomplete_order' );
		}
		if ( WC()->session->get( 'dibs_order_data' ) ) {
			WC()->session->__unset( 'dibs_order_data' );
		}
		if ( WC()->session->get( 'dibs_payment_id' ) ) {
			WC()->session->__unset( 'dibs_payment_id' );
		}
		if ( WC()->session->get( 'dibs_customer_order_note' ) ) {
			WC()->session->__unset( 'dibs_customer_order_note' );
		}
		if ( WC()->session->get( 'nets_easy_currency' ) ) {
			WC()->session->__unset( 'nets_easy_currency' );
		}

		if ( WC()->session->get( 'dibs_cart_contains_subscription' ) ) {
			WC()->session->__unset( 'dibs_cart_contains_subscription' );
		}
	}
}

/**
 * Get Nets locale.
 *
 * @return string
 */
function wc_dibs_get_locale() {
	switch ( get_locale() ) {
		case 'sv_SE':
			$language = 'sv-SE';
			break;
		case 'nb_NO':
		case 'nn_NO':
			$language = 'nb-NO';
			break;
		case 'da_DK':
			$language = 'da-DK';
			break;
		case 'de_DE':
		case 'de_CH':
		case 'de_AT':
		case 'de_DE_formal':
			$language = 'de-DE';
			break;
		case 'pl_PL':
			$language = 'pl-PL';
			break;
		case 'fi':
			$language = 'fi-FI';
			break;
		case 'fr_FR':
		case 'fr_BE':
			$language = 'fr-FR';
			break;
		case 'nl_NL':
		case 'nl_BE':
			$language = 'nl-NL';
			break;
		case 'es_ES':
			$language = 'es-ES';
			break;
		default:
			$language = 'en-GB';
	}

	return $language;
}



/**
 * Get name cleaned for Nets.
 *
 * @param string $name Name to be cleaned.
 */
function wc_dibs_clean_name( $name ) {
	$not_allowed_characters = array( '<', '>', '\\', '"', '&' );
	$name                   = wp_strip_all_tags( $name );
	$name                   = str_replace( $not_allowed_characters, '', $name );

	return substr( $name, 0, 128 );
}

/**
 * Confirm the order in WooCommerce.
 *
 * @param int $order_id Woocommerce order id.
 *
 * @return void
 */
function wc_dibs_confirm_dibs_order( $order_id ) {
	$order      = wc_get_order( $order_id );
	$payment_id = $order->get_meta( '_dibs_payment_id' );
	$settings   = get_option( 'woocommerce_dibs_easy_settings' );

	if ( 'dibs_easy' === $order->get_payment_method() ) {
		// Get checkout flow to see if we need to handle logic for embedded flow.
		$checkout_flow = $settings['checkout_flow'] ?? 'embedded';
	} else {
		// For stand alone payment methods, use redirect.
		$checkout_flow = 'redirect';
	}

	if ( null === $payment_id ) {
		$payment_id = WC()->session->get( 'dibs_payment_id' );
	}
	if ( '' !== $order->get_shipping_method() ) {
		wc_dibs_save_shipping_reference_to_order( $order_id );
	}

	$request = Nets_Easy()->api->get_nets_easy_order( $payment_id, $order_id );

	if ( is_wp_error( $request ) ) {
		$order->add_order_note(
			sprintf(
				/* translators: %s: Error message */
				__( 'Nexi Checkout: Error when confirming order: %s', 'dibs-easy-for-woocommerce' ),
				$request->get_error_message()
			)
		);
		return;
	}

	if ( isset( $request['payment']['summary']['reservedAmount'] ) || isset( $request['payment']['summary']['chargedAmount'] ) || isset( $request['payment']['subscription']['id'] ) ) {

		do_action( 'dibs_easy_process_payment', $order_id, $request );

		$order->update_meta_data( 'dibs_payment_type', $request['payment']['paymentDetails']['paymentType'] );
		$order->update_meta_data( 'dibs_payment_method', $request['payment']['paymentDetails']['paymentMethod'] );
		$order->update_meta_data( '_dibs_date_paid', gmdate( 'Y-m-d H:i:s' ) );
		$order->save();

		wc_dibs_maybe_add_invoice_fee( $order );

		if ( 'CARD' === $request['payment']['paymentDetails']['paymentType'] ) { // phpcs:ignore
			$order->update_meta_data( 'dibs_customer_card', $request['payment']['paymentDetails']['cardDetails']['maskedPan'] );
			$order->save();
		}

		// Update order reference if this is embedded checkout flow.
		$_checkout_flow = $order->get_meta( '_dibs_checkout_flow' );
		$checkout_flow  = ! empty( $_checkout_flow ) ? $_checkout_flow : $checkout_flow;
		if ( 'embedded' === $checkout_flow ) {
			$order_reference_response = Nets_Easy()->api->update_nets_easy_order_reference( $payment_id, $order_id );
			if ( is_wp_error( $order_reference_response ) ) {
				$order->add_order_note(
					sprintf(
						/* translators: %s: Error message */
						__( 'Nexi Checkout: Error when updating order reference to Nets. Error message : %s', 'dibs-easy-for-woocommerce' ),
						$order_reference_response->get_error_message()
					)
				);
			}
		}

		if ( isset( $request['payment']['charges'][0]['chargeId'] ) && ! empty( $request['payment']['charges'][0]['chargeId'] ) ) {
			// Get the DIBS order charge ID.
			$dibs_charge_id = $request['payment']['charges'][0]['chargeId'];
			$order->update_meta_data( '_dibs_charge_id', $dibs_charge_id );
			$order->save();

			// Translators: Nexi Checkout Payment ID.
			$order->add_order_note( sprintf( __( 'New payment created in Nexi Checkout with Payment ID %1$s. Payment type - %2$s. Charge ID %3$s.', 'dibs-easy-for-woocommerce' ), $payment_id, $request['payment']['paymentDetails']['paymentMethod'], $dibs_charge_id ) );
		} else {
			// Translators: Nexi Checkout Payment ID.
			$order->add_order_note( sprintf( __( 'New payment created in Nexi Checkout with Payment ID %1$s. Payment type - %2$s. Awaiting charge.', 'dibs-easy-for-woocommerce' ), $payment_id, $request['payment']['paymentDetails']['paymentType'] ) );
		}
		$order->payment_complete( $payment_id );

	} else {
		// Purchase not finalized in DIBS.
		// If this is a redirect checkout flow let's redirect the customer to cart page.
		if ( 'embedded' !== $checkout_flow ) {
			wp_safe_redirect( html_entity_decode( $order->get_cancel_order_url(), ENT_QUOTES ) );
			exit;
		}
	}
}

/**
 * Save shipping reference to Order.
 *
 * @param int $order_id order id.
 * @return void
 */
function wc_dibs_save_shipping_reference_to_order( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( isset( WC()->session ) && method_exists( WC()->session, 'get' ) ) {
		$packages        = WC()->shipping->get_packages();
		$chosen_methods  = WC()->session->get( 'chosen_shipping_methods' );
		$chosen_shipping = $chosen_methods[0];
		foreach ( $packages as $i => $package ) {
			foreach ( $package['rates'] as $method ) {
				if ( $chosen_shipping === $method->id ) {
					$order->update_meta_data( '_nets_shipping_reference', 'shipping|' . $method->id );
					$order->save();
				}
			}
		}
	}
}

/**
 * Add invoice fee to order.
 *
 * @param object $order WooCommerce order.
 * @return void
 */
function wc_dibs_maybe_add_invoice_fee( $order ) {
	// Add invoice fee to order.
	$order_id = $order->get_id();
	if ( 'INVOICE' === $order->get_meta( 'dibs_payment_type' ) ) {
			$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
		if ( isset( $dibs_settings['dibs_invoice_fee'] ) && ! empty( $dibs_settings['dibs_invoice_fee'] ) ) {
			$invoice_fee_id = $dibs_settings['dibs_invoice_fee'];
			$invoice_fee    = wc_get_product( $invoice_fee_id );

			if ( is_object( $invoice_fee ) ) {
				$fee      = new WC_Order_Item_Fee();
				$fee_args = array(
					'name'  => $invoice_fee->get_name(),
					'total' => wc_get_price_excluding_tax( $invoice_fee ),
				);

				$fee->set_props( $fee_args );
				if ( 'none' === $invoice_fee->get_tax_status() ) {
					$tax_amount = '0';
					$fee->set_total_tax( $tax_amount );
					$fee->set_tax_status( $invoice_fee->get_tax_status() );
				} else {
					$fee->set_tax_class( $invoice_fee->get_tax_class() );
				}

				$order->add_item( $fee );
				$order->calculate_totals();
				$order->save();
			}
		}
	}
}

/**
 * Prints error message as notices.
 *
 * @param WP_Error $wp_error A WordPress error object.
 *
 * @return void
 */
function dibs_easy_print_error_message( $wp_error ) {
	$error_message = $wp_error->get_error_message();

	if ( is_array( $error_message ) ) {
		// Rather than assuming the first element is a string, we'll force a string conversion instead.
		$error_message = implode( ' ', $error_message );
	}

	if ( is_ajax() ) {
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $error_message, 'error' );
		}
	} elseif ( function_exists( 'wc_print_notice' ) ) {
			wc_print_notice( $error_message, 'error' );
	}
}

/**
 * Finds an order based on a payment ID (the Nets order number).
 *
 * @param string $payment_id Nets order number saved as Payment ID in WC order.
 * @return object|bool The WooCommerce order, or false if the order could not be found.
 */
function nets_easy_get_order_by_purchase_id( $payment_id, $date_after = null ) {

	$args = array(
		'meta_key'     => '_dibs_payment_id',
		'meta_value'   => wc_clean( wp_unslash( $payment_id ) ),
		'meta_compare' => '=',
		'order'        => 'DESC',
		'orderby'      => 'date',
		'limit'        => 1,
	);

	if ( $date_after ) {
		$args['date_after'] = $date_after;
	}

	$orders = wc_get_orders( $args );

	// If the orders array is empty, return false.
	if ( empty( $orders ) ) {
		return false;
	}

	// Get the first order in the array.
	$order = reset( $orders );

	// Validate that the order actual has the metadata we're looking for, and that it is the same.
	$meta_value = $order->get_meta( '_dibs_payment_id', true );

	// If the meta value is not the same as the Nexi payment id, return false.
	if ( $meta_value !== $payment_id ) {
		return false;
	}

	return $order;
}

function nets_easy_all_payment_method_ids() {
	return array( 'dibs_easy', 'nets_easy_card', 'nets_easy_sofort', 'nets_easy_trustly', 'nets_easy_swish', 'nets_easy_ratepay_sepa' );
}
