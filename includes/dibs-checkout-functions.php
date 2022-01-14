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
 * Echoes DIBS Easy iframe snippet.
 */
function wc_dibs_show_snippet() {
	$private_key = wc_dibs_get_private_key();
	$payment_id  = wc_dibs_get_payment_id();
	$locale      = wc_dibs_get_locale();

	if ( ! is_array( $payment_id ) ) {
		?>
	<div id="dibs-complete-checkout"></div>
	<script type="text/javascript">
		var checkoutOptions = {
					checkoutKey: "<?php _e( $private_key ); ?>", 	//[Required] Test or Live GUID with dashes.
					paymentId : "<?php _e( $payment_id ); ?>", 		//[required] GUID without dashes.
					containerId : "dibs-complete-checkout", 		//[optional] defaultValue: dibs-checkout-content.
					language: "<?php _e( $locale ); ?>",            //[optional] defaultValue: en-GB.
		};
		var dibsCheckout = new Dibs.Checkout(checkoutOptions);
		console.log(checkoutOptions);
	</script>
		<?php
	} else {
		?>
		<ul class="woocommerce-error" role="alert">
			<li><?php _e( 'Nets API Error: ' . $payment_id['error_message'] ); ?></li>
		</ul>
		<?php
	}
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
		if ( WC()->session->get( 'dibs_currency' ) ) {
			WC()->session->__unset( 'dibs_currency' );
		}
	}
}

/**
 * Get Nets locale.
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
 * Get payment id.
 */
function wc_dibs_get_payment_id() {
	if ( isset( $_POST['dibs_payment_id'] ) && ! empty( $_POST['dibs_payment_id'] ) ) {
		return $_POST['dibs_payment_id'];
	}

	if ( ! empty( WC()->session->get( 'dibs_payment_id' ) && WC()->session->get( 'dibs_currency' ) === get_woocommerce_currency() ) ) {
		return WC()->session->get( 'dibs_payment_id' );
	} else {
		WC()->session->set( 'chosen_payment_method', 'dibs_easy' );

		$request = new DIBS_Requests_Create_DIBS_Order();
		$request = json_decode( $request->request() );
		if ( isset( $request->paymentId ) ) { // phpcs:ignore
			WC()->session->set( 'dibs_payment_id', $request->paymentId ); // phpcs:ignore
			WC()->session->set( 'dibs_currency', get_woocommerce_currency() );

			// Set a transient for this paymentId. It's valid in DIBS system for 20 minutes.
			set_transient( 'dibs_payment_id_' . $request->paymentId, $request->paymentId, 15 * MINUTE_IN_SECONDS ); // phpcs:ignore

			return $request->paymentId; // phpcs:ignore
		} else {
			foreach ( $request->errors as $error ) {
				$error_message = $error[0];
			}
			echo( "<script>console.log('Nets error: " . $error_message . "');</script>" ); // phpcs:ignore
			return array(
				'result'        => false,
				'error_message' => $error_message,
			);
		}
	}
}

/**
 * Get order ID.
 */
function wc_dibs_get_order_id() {
	// Create an empty WooCommerce order and get order id if one is not made already.
	if ( WC()->session->get( 'dibs_incomplete_order' ) === null ) {
		$order    = wc_create_order();
		$order_id = $order->get_id();
		// Set the order id as a session variable.
		WC()->session->set( 'dibs_incomplete_order', $order_id );
		$order->save();
	} else {
		$order_id = WC()->session->get( 'dibs_incomplete_order' );
		$order    = wc_get_order( $order_id );
		$order->save();
	}

	return $order_id;
}

/**
 * Get private ID.
 */
function wc_dibs_get_private_key() {
	$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
	$testmode      = 'yes' === $dibs_settings['test_mode'];
	$private_key   = $testmode ? $dibs_settings['dibs_test_checkout_key'] : $dibs_settings['dibs_checkout_key'];
	return apply_filters( 'dibs_easy_request_checkout_key', $private_key, $testmode );
}

/**
 * Get name cleaned for Nets.
 *
 * @param string $name Name to be cleaned.
 */
function wc_dibs_clean_name( $name ) {
	$regex = '/[^!#$%()*+,-.\/:;=?@\[\]\\\^_`{}|~a-zA-Z0-9\x{00A1}-\x{00AC}\x{00AE}-\x{00FF}\x{0100}-\x{017F}\x{0180}-\x{024F}\x{0250}-\x{02AF}\x{02B0}-\x{02FF}\x{0300}-\x{036F}\s]+/u';
	$name  = preg_replace( $regex, '', $name );

	return substr( $name, 0, 128 );
}

/**
 * Confirm the order in WooCommerce.
 *
 * @param string $order_id Woocommerce order id.
 */
function wc_dibs_confirm_dibs_order( $order_id ) {
	$order         = wc_get_order( $order_id );
	$payment_id    = get_post_meta( $order_id, '_dibs_payment_id', true );
	$settings      = get_option( 'woocommerce_dibs_easy_settings' );
	$checkout_flow = ( isset( $settings['checkout_flow'] ) ) ? $settings['checkout_flow'] : 'embedded';
	$auto_capture  = ( isset( $settings['auto_capture'] ) ) ? $settings['auto_capture'] : 'no';

	if ( '' !== $order->get_shipping_method() ) {
		wc_dibs_save_shipping_reference_to_order( $order_id );
	}

	$request = new DIBS_Requests_Get_DIBS_Order( $payment_id, $order_id );
	$request = $request->request();

	if ( isset( $request->payment->summary->reservedAmount ) || isset( $request->payment->summary->chargedAmount ) || isset( $request->payment->subscription->id ) ) {

		do_action( 'dibs_easy_process_payment', $order_id, $request );

		update_post_meta( $order_id, 'dibs_payment_type', $request->payment->paymentDetails->paymentType );
		update_post_meta( $order_id, 'dibs_payment_method', $request->payment->paymentDetails->paymentMethod );
		update_post_meta( $order_id, '_dibs_date_paid', gmdate( 'Y-m-d H:i:s' ) );

		wc_dibs_maybe_add_invoice_fee( $order );

		if ( 'CARD' == $request->payment->paymentDetails->paymentType ) { // phpcs:ignore
			update_post_meta( $order_id, 'dibs_customer_card', $request->payment->paymentDetails->cardDetails->maskedPan );
		}

		if ( 'A2A' === $request->payment->paymentDetails->paymentType ) {

			// Get the DIBS order charge ID.
			$dibs_charge_id = $request->payment->charges[0]->chargeId;
			update_post_meta( $order_id, '_dibs_charge_id', $dibs_charge_id );

			// Translators: Nets Easy Payment ID.
			$order->add_order_note( sprintf( __( 'New payment created in Nets Easy with Payment ID %1$s. Payment type - %2$s. Charge ID %3$s.', 'dibs-easy-for-woocommerce' ), $payment_id, $request->payment->paymentDetails->paymentMethod, $dibs_charge_id ) );
		} else {
			// Translators: Nets Easy Payment ID.
			$order->add_order_note( sprintf( __( 'New payment created in Nets Easy with Payment ID %1$s. Payment type - %2$s. Awaiting charge.', 'dibs-easy-for-woocommerce' ), $payment_id, $request->payment->paymentDetails->paymentType ) );
		}
		$order->payment_complete( $payment_id );

		if ( 'yes' === $auto_capture ) {
			Nets_Easy()->order_management->dibs_order_completed( $order_id );
		}
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
	if ( method_exists( WC()->session, 'get' ) ) {
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

