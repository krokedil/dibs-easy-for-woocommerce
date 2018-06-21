<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Echoes DIBS Easy iframe snippet.
 */
function wc_dibs_show_snippet() {
	$container = '<div id="dibs-complete-checkout"></div>';
	echo $container;
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
		echo '<input style="display:none" type="radio" name="payment_method" value="dibs_easy"/>';
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
	WC()->session->__unset( 'dibs_incomplete_order' );
	WC()->session->__unset( 'dibs_order_data' );
}

function wc_dibs_get_locale() {
	switch ( get_locale() ) {
		case 'sv_SE' :
			$language = 'sv-SE';
			break;
		case 'nb_NO' :
		case 'nn_NO' :
			$language = 'nb-NO';
			break;
		case 'da_DK' :
			$language = 'da-DK';
			break;
		default :
			$language = 'en-GB';
	}

	return $language;
}
