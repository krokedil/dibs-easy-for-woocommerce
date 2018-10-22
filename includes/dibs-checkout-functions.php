<?php
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
					checkoutKey: "<?php _e( $private_key ); ?>", 	//[Required] Test or Live GUID with dashes
					paymentId : "<?php _e( $payment_id ); ?>", 		//[required] GUID without dashes
					containerId : "dibs-complete-checkout", 		//[optional] defaultValue: dibs-checkout-content
					language: "<?php _e( $locale ); ?>",            //[optional] defaultValue: en-GB
		};
		var dibsCheckout = new Dibs.Checkout(checkoutOptions);
		console.log(checkoutOptions);
	</script>
	<?php
	} else {
		?>
		<ul class="woocommerce-error" role="alert">
			<li><?php _e( 'DIBS API Error: ' . $payment_id['error_message'] ); ?></li>
		</ul>
		<?php
		// echo 'DIBS API Error: ' . $payment_id['error_message'];
	}
}


/**
 * Shows Customer order notes in DIBS Easy Checkout page.
 */
function wc_dibs_show_customer_order_notes() {

	if ( apply_filters( 'woocommerce_enable_order_notes_field', true ) ) {
		$form_field = WC()->checkout()->get_checkout_fields( 'order' );
		woocommerce_form_field( 'order_comments', $form_field['order_comments'] );
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
				<?php echo $select_another_method_text; ?>
			</a>
		</p>
		<?php
	}
}

/**
 * Add WooCommerce checkout form fields to checkout page.
 * These fields are hidden from the customer but needed for
 * when the checkout form is being submitted.
 */
function wc_dibs_add_woocommerce_checkout_form_fields() {

	echo '<div id="dibs-hidden" style="display:none;">';
		do_action( 'woocommerce_checkout_billing' );
		do_action( 'woocommerce_checkout_shipping' );
		$order_button_text = __( 'Pay for order', 'woocommerce' );
		echo apply_filters( 'woocommerce_pay_order_button_html', '<button type="submit" class="button alt" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' );
		echo '<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="terms"' . checked( apply_filters( 'woocommerce_terms_is_checked_default', isset( $_POST['terms'] ) ), true ) . ' id="terms" />';
		wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' );
		echo '<input style="display:none" type="radio" name="payment_method" value="dibs_easy" checked="checked"/>';
	echo '</div>';
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
	}
}

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
		default:
			$language = 'en-GB';
	}

	return $language;
}

function wc_dibs_get_payment_id() {
	if ( isset( $_POST['dibs_payment_id'] ) && ! empty( $_POST['dibs_payment_id'] ) ) {
		return $_POST['dibs_payment_id'];
	}

	if ( ! empty( WC()->session->get( 'dibs_payment_id' ) ) ) {
		return WC()->session->get( 'dibs_payment_id' );
	} else {
		WC()->session->set( 'chosen_payment_method', 'dibs_easy' );

		// $order_id = wc_dibs_get_order_id();
		// Get the datastring containing the cart data
		// $get_cart   = new DIBS_Get_WC_Cart();
		// $datastring = $get_cart->create_cart( $order_id );
		// Make the request
		// $request = new DIBS_Requests();
		// $endpoint_sufix = 'payments/';
		// $request = $request->make_request( 'POST', $datastring, $endpoint_sufix );
		$request = new DIBS_Requests_Create_DIBS_Order();
		$request = json_decode( $request->request() );
		if ( array_key_exists( 'paymentId', $request ) ) {
			WC()->session->set( 'dibs_payment_id', $request->paymentId );

			// Set a transient for this paymentId. It's valid in DIBS system for 20 minutes.
			set_transient( 'dibs_payment_id_' . $request->paymentId, $request->paymentId, 15 * MINUTE_IN_SECONDS );

			return $request->paymentId;
		} else {
			foreach ( $request->errors as $error ) {
				$error_message = $error[0];
			}
			echo( "<script>console.log('DIBS error: " . $error_message . "');</script>" );
			return array(
				'result'        => false,
				'error_message' => $error_message,
			);
		}
	}
}

function wc_dibs_get_order_id() {
	// Create an empty WooCommerce order and get order id if one is not made already
	if ( WC()->session->get( 'dibs_incomplete_order' ) === null ) {
		$order    = wc_create_order();
		$order_id = $order->get_id();
		// Set the order id as a session variable
		WC()->session->set( 'dibs_incomplete_order', $order_id );
		// $order->update_status( 'dibs-incomplete' );
		$order->save();
	} else {
		$order_id = WC()->session->get( 'dibs_incomplete_order' );
		$order    = wc_get_order( $order_id );
		// $order->update_status( 'dibs-incomplete' );
		$order->save();
	}

	return $order_id;
}

function wc_dibs_get_private_key() {
	$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
	$testmode      = 'yes' === $dibs_settings['test_mode'];
	$private_key   = $testmode ? $dibs_settings['dibs_test_checkout_key'] : $dibs_settings['dibs_checkout_key'];
	return $private_key;
}

function wc_dibs_clean_name( $name ) {
	$name = preg_replace( '/[^!#$%()*+,-.\/:;=?@\[\]\\\^_`{}|~a-zA-Z0-9\s]+/i', '', $name );

	return $name;
}

