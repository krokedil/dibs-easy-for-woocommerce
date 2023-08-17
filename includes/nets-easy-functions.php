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
	$order         = wc_get_order( $order_id );
	$payment_id    = get_post_meta( $order_id, '_dibs_payment_id', true );
	$settings      = get_option( 'woocommerce_dibs_easy_settings' );
	$checkout_flow = $settings['checkout_flow'] ?? 'embedded';

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
				__( 'Nets Easy: Error when confirming order: %s', 'dibs-easy-for-woocommerce' ),
				$request->get_error_message()
			)
		);
		return;
	}

	if ( isset( $request['payment']['summary']['reservedAmount'] ) || isset( $request['payment']['summary']['chargedAmount'] ) || isset( $request['payment']['subscription']['id'] ) ) {

		do_action( 'dibs_easy_process_payment', $order_id, $request );

		update_post_meta( $order_id, 'dibs_payment_type', $request['payment']['paymentDetails']['paymentType'] );
		update_post_meta( $order_id, 'dibs_payment_method', $request['payment']['paymentDetails']['paymentMethod'] );
		update_post_meta( $order_id, '_dibs_date_paid', gmdate( 'Y-m-d H:i:s' ) );

		wc_dibs_maybe_add_invoice_fee( $order );

		if ( 'CARD' === $request['payment']['paymentDetails']['paymentType'] ) { // phpcs:ignore
			update_post_meta( $order_id, 'dibs_customer_card', $request['payment']['paymentDetails']['cardDetails']['maskedPan'] );
		}

		// Update order reference if this is embedded checkout flow.
		if ( 'embedded' === $checkout_flow ) {
			$order_reference_response = Nets_Easy()->api->update_nets_easy_order_reference( $payment_id, $order_id );
			if ( is_wp_error( $order_reference_response ) ) {
				$order->add_order_note(
					sprintf(
						/* translators: %s: Error message */
						__( 'Nets Easy: Error when updating order reference to Nets. Error message : %s', 'dibs-easy-for-woocommerce' ),
						$order_reference_response->get_error_message()
					)
				);
			}
		}

		if ( isset( $request['payment']['charges'][0]['chargeId'] ) && ! empty( $request['payment']['charges'][0]['chargeId'] ) ) {
			// Get the DIBS order charge ID.
			$dibs_charge_id = $request['payment']['charges'][0]['chargeId'];
			update_post_meta( $order_id, '_dibs_charge_id', $dibs_charge_id );

			// Translators: Nets Easy Payment ID.
			$order->add_order_note( sprintf( __( 'New payment created in Nets Easy with Payment ID %s. Payment type - %s. Charge ID %3$s.', 'dibs-easy-for-woocommerce' ), $payment_id, $request['payment']['paymentDetails']['paymentMethod'], $dibs_charge_id ) );
		} else {
			// Translators: Nets Easy Payment ID.
			$order->add_order_note( sprintf( __( 'New payment created in Nets Easy with Payment ID %1$s. Payment type - %2$s. Awaiting charge.', 'dibs-easy-for-woocommerce' ), $payment_id, $request['payment']['paymentDetails']['paymentType'] ) );
		}
		$order->payment_complete( $payment_id );

	} else {
		// Purchase not finalized in DIBS.
		// If this is a redirect checkout flow let's redirect the customer to cart page.
		if ( 'embedded' !== $checkout_flow ) {
			wp_safe_redirect( html_entity_decode( $order->get_cancel_order_url() ) );
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
	if ( isset( WC()->session ) && method_exists( WC()->session, 'get' ) ) {
		$packages        = WC()->shipping->get_packages();
		$chosen_methods  = WC()->session->get( 'chosen_shipping_methods' );
		$chosen_shipping = $chosen_methods[0];
		foreach ( $packages as $i => $package ) {
			foreach ( $package['rates'] as $method ) {
				if ( $chosen_shipping === $method->id ) {
					update_post_meta( $order_id, '_nets_shipping_reference', 'shipping|' . $method->id );
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
	if ( 'INVOICE' === get_post_meta( $order_id, 'dibs_payment_type', true ) ) {
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
	} else {
		if ( function_exists( 'wc_print_notice' ) ) {
			wc_print_notice( $error_message, 'error' );
		}
	}
}

/**
 * Finds an Order ID based on a payment ID (the Nets order number).
 *
 * @param string $payment_id Nets order number saved as Payment ID in WC order.
 * @return int The ID of an order, or 0 if the order could not be found.
 */
function nets_easy_get_order_id_by_purchase_id( $payment_id ) {
	$query_args = array(
		'fields'      => 'ids',
		'post_type'   => wc_get_order_types(),
		'post_status' => array_keys( wc_get_order_statuses() ),
		'meta_key'    => '_dibs_payment_id', // phpcs:ignore WordPress.DB.SlowDBQuery -- Slow DB Query is ok here, we need to limit to our meta key.
		'meta_value'  => sanitize_text_field( wp_unslash( $payment_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery -- Slow DB Query is ok here, we need to limit to our meta key.
		'order'       => 'DESC',
		'date_query'  => array(
			array(
				'after' => '30 day ago',
			),
		),
	);

	$orders = get_posts( $query_args );

	if ( $orders ) {
		$order_id = $orders[0];
	} else {
		$order_id = 0;
	}

	return $order_id;
}

function nets_easy_all_payment_method_ids() {
	return array( 'dibs_easy', 'nets_easy_card', 'nets_easy_sofort', 'nets_easy_swish' );
}
