<?php
/**
 * AJAX endpoints for Express Checkout (Apple Pay / Google Pay).
 *
 * @package Nexi/ExpressCheckout
 */

namespace Krokedil\Nexi\ExpressCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles the Express Checkout AJAX endpoints.
 */
class AJAX {

	/**
	 * Nonce action used to verify all Express Checkout AJAX requests.
	 *
	 * Deliberately separate from the legacy 'nets_checkout' nonce used by Nets_Easy_Ajax, since
	 * these endpoints are reachable from a different trust boundary (before any Nexi session may
	 * exist yet).
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'nexi_express_checkout';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'wc_ajax_nexi_express_create_session', array( $this, 'create_session' ) );
		add_action( 'wc_ajax_nexi_express_shipping_address_change', array( $this, 'shipping_address_change' ) );
		add_action( 'wc_ajax_nexi_express_payment_completed', array( $this, 'payment_completed' ) );
	}

	/**
	 * Creates (or replaces) a Nexi Express payment session from either a single product (product
	 * page "buy now") or the current cart as-is (cart page).
	 *
	 * @return void
	 */
	public function create_session() {
		$this->verify_nonce();

		$context = isset( $_POST['context'] ) ? sanitize_key( wp_unslash( $_POST['context'] ) ) : 'cart'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in verify_nonce().

		if ( 'product' === $context ) {
			$added = $this->add_single_product_to_cart();
			if ( is_wp_error( $added ) ) {
				wp_send_json_error( $added->get_error_message() );
			}
		}

		$response = Session::create_from_cart();
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		}

		wp_send_json_success( array( 'paymentId' => $response['paymentId'] ) );
	}

	/**
	 * Validates and adds a single product (optionally a variation) to the cart, replacing its
	 * current contents. This is a destructive "buy now" replace, correct for a single-product
	 * express button but not for the cart-page context.
	 *
	 * @return true|\WP_Error
	 */
	private function add_single_product_to_cart() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in verify_nonce().
		$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
		$quantity     = isset( $_POST['quantity'] ) ? max( 1, absint( $_POST['quantity'] ) ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return new \WP_Error( 'nexi_express_checkout', __( 'This product is not available for Express Checkout.', 'dibs-easy-for-woocommerce' ) );
		}

		if ( ExpressCheckout::is_subscription_product( $product ) ) {
			return new \WP_Error( 'nexi_express_checkout', __( 'Express Checkout is not available for subscription products.', 'dibs-easy-for-woocommerce' ) );
		}

		WC()->cart->empty_cart();
		$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
		if ( ! $added ) {
			return new \WP_Error( 'nexi_express_checkout', __( 'Could not add the product to the cart.', 'dibs-easy-for-woocommerce' ) );
		}

		return true;
	}

	/**
	 * Handles the Express widget's shippingaddresschange event: syncs the WC customer address,
	 * auto-selects a shipping method per package using WooCommerce's own default selection logic
	 * (the Express widget has no way to offer method choice inside the wallet sheet), recalculates
	 * totals, and pushes the updated order to Nexi so the widget can refresh its displayed total.
	 *
	 * @return void
	 */
	public function shipping_address_change() {
		$this->verify_nonce();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified in verify_nonce().
		$payment_id = isset( $_POST['payment_id'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_id'] ) ) : '';
		if ( empty( $payment_id ) || WC()->session->get( 'dibs_payment_id' ) !== $payment_id ) {
			wp_send_json_error( __( 'Payment session mismatch.', 'dibs-easy-for-woocommerce' ) );
		}

		$country     = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
		$postal_code = isset( $_POST['postal_code'] ) ? sanitize_text_field( wp_unslash( $_POST['postal_code'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$country = strlen( $country ) > 2 ? dibs_get_iso_2_country( $country ) : $country;

		WC()->customer->set_billing_country( $country );
		WC()->customer->set_shipping_country( $country );
		WC()->customer->set_billing_postcode( $postal_code );
		WC()->customer->set_shipping_postcode( $postal_code );
		WC()->customer->save();

		wc_dibs_calculate_totals();
		$this->auto_select_shipping_methods();
		wc_dibs_calculate_totals();

		$update = Nets_Easy()->api->update_nets_easy_order( $payment_id );
		if ( is_wp_error( $update ) ) {
			wp_send_json_error( $update->get_error_message() );
		}

		WC()->session->set( 'nets_easy_last_update_hash', WC()->cart->get_cart_hash() );

		wp_send_json_success( array( 'total' => WC()->cart->get_total( 'edit' ) ) );
	}

	/**
	 * Auto-selects a shipping method for each package using WooCommerce core's own default
	 * "no active choice yet" logic (session-chosen method if still valid, otherwise the first rate
	 * in the merchant's own configured zone order). This is the same default WooCommerce already
	 * applies everywhere else, so the Express total never disagrees with what the normal
	 * cart/checkout flow would have shown for the same address.
	 *
	 * @return void
	 */
	private function auto_select_shipping_methods() {
		if ( ! WC()->cart->needs_shipping() ) {
			return;
		}

		$packages = WC()->shipping()->get_packages();
		$chosen   = WC()->session->get( 'chosen_shipping_methods', array() );

		foreach ( $packages as $key => $package ) {
			if ( empty( $package['rates'] ) ) {
				continue;
			}
			$chosen[ $key ] = wc_get_chosen_shipping_method_for_package( $key, $package );
		}

		WC()->session->set( 'chosen_shipping_methods', $chosen );
	}

	/**
	 * Handles the Express widget's paymentcompleted event: syncs the confirmed consumer/shipping
	 * address from Nexi (as a pre-fill safety net for the checkout page), marks the Express
	 * payment as completed, and hands back the checkout URL to redirect to.
	 *
	 * @return void
	 */
	public function payment_completed() {
		$this->verify_nonce();

		$payment_id = isset( $_POST['payment_id'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in verify_nonce().
		if ( empty( $payment_id ) || WC()->session->get( 'dibs_payment_id' ) !== $payment_id ) {
			wp_send_json_error( __( 'Payment session mismatch.', 'dibs-easy-for-woocommerce' ) );
		}

		$order_response = Nets_Easy()->api->get_nets_easy_order( $payment_id );
		if ( ! is_wp_error( $order_response ) ) {
			$this->sync_customer_from_order( $order_response );
		}

		// Stored together with the cart hash so a later, unrelated cart change naturally stops this
		// from being treated as an in-progress Express Checkout finalize. See
		// nexi_express_checkout_is_completed_for_current_cart().
		WC()->session->set( 'dibs_express_checkout_completed', WC()->cart->get_cart_hash() );

		wp_send_json_success( array( 'redirect' => wc_get_checkout_url() ) );
	}

	/**
	 * Syncs the WC customer from a completed Nexi order's consumer/shipping data. Used as a
	 * pre-fill safety net; the checkout page's own finalize script re-fetches and applies the
	 * authoritative data to the actual checkout form fields.
	 *
	 * @param array $order_response The Nexi order response.
	 * @return void
	 */
	private function sync_customer_from_order( array $order_response ) {
		$consumer = $order_response['payment']['consumer'] ?? array();
		if ( empty( $consumer ) ) {
			return;
		}

		$address = $consumer['shippingAddress'] ?? array();
		$person  = $consumer['privatePerson'] ?? ( $consumer['company']['contact'] ?? array() );

		$customer = WC()->customer;

		if ( ! empty( $consumer['email'] ) ) {
			$customer->set_billing_email( $consumer['email'] );
		}

		if ( ! empty( $person['firstName'] ) ) {
			$customer->set_billing_first_name( $person['firstName'] );
			$customer->set_shipping_first_name( $person['firstName'] );
		}

		if ( ! empty( $person['lastName'] ) ) {
			$customer->set_billing_last_name( $person['lastName'] );
			$customer->set_shipping_last_name( $person['lastName'] );
		}

		if ( ! empty( $address['addressLine1'] ) ) {
			$customer->set_billing_address_1( $address['addressLine1'] );
			$customer->set_shipping_address_1( $address['addressLine1'] );
		}

		if ( ! empty( $address['postalCode'] ) ) {
			$customer->set_billing_postcode( $address['postalCode'] );
			$customer->set_shipping_postcode( $address['postalCode'] );
		}

		if ( ! empty( $address['city'] ) ) {
			$customer->set_billing_city( $address['city'] );
			$customer->set_shipping_city( $address['city'] );
		}

		if ( ! empty( $address['country'] ) ) {
			$country = strlen( $address['country'] ) > 2 ? dibs_get_iso_2_country( $address['country'] ) : $address['country'];
			$customer->set_billing_country( $country );
			$customer->set_shipping_country( $country );
		}

		$customer->save();
	}

	/**
	 * Verifies the Express Checkout AJAX nonce, or ends the request with an error response.
	 *
	 * @return void
	 */
	private function verify_nonce() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json_error( 'bad_nonce' );
		}
	}
}
