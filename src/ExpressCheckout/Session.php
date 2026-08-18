<?php
/**
 * Handles creation of Nexi Express Checkout payment sessions.
 *
 * @package Nexi/ExpressCheckout
 */

namespace Krokedil\Nexi\ExpressCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates fresh Nexi Express payment sessions from the current WooCommerce cart.
 */
class Session {

	/**
	 * Creates a fresh Nexi Express payment session from the current WooCommerce cart.
	 *
	 * Always terminates and replaces any pre-existing Nexi session rather than reusing it, since
	 * the cart contents may have just changed (e.g. a product-page "buy now" replace), and an
	 * existing session could belong to a different, stale cart.
	 *
	 * @return array|\WP_Error The Nexi payment response, or a WP_Error on failure.
	 */
	public static function create_from_cart() {
		$existing_payment_id = WC()->session->get( 'dibs_payment_id' );
		if ( $existing_payment_id ) {
			nexi_terminate_session( $existing_payment_id );
			wc_dibs_unset_sessions();
		}

		wc_dibs_calculate_totals();

		$inject_integration_type = function ( $args ) {
			$args['checkout']['integrationType'] = 'Express';
			return $args;
		};

		// Express Checkout always uses the embedded order/checkout payload shape, regardless of
		// the merchant's own main checkout_flow setting (redirect/embedded/inline/overlay).
		add_filter( 'dibs_easy_create_order_args', $inject_integration_type, 20 );
		$response = Nets_Easy()->api->create_nets_easy_order( array( 'checkout_flow' => 'embedded' ) );
		remove_filter( 'dibs_easy_create_order_args', $inject_integration_type, 20 );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( empty( $response['paymentId'] ) ) {
			return new \WP_Error( 'nexi_express_checkout', __( 'Nexi Checkout did not return a payment ID.', 'dibs-easy-for-woocommerce' ) );
		}

		$payment_id = $response['paymentId'];

		WC()->session->set( 'dibs_payment_id', $payment_id );
		WC()->session->set( 'nets_easy_currency', get_woocommerce_currency() );
		WC()->session->set( 'nets_easy_last_update_hash', WC()->cart->get_cart_hash() );
		// Express Checkout excludes subscription products (a Nexi Checkout limitation), enforced
		// before this method is ever called.
		WC()->session->set( 'dibs_cart_contains_subscription', false );

		set_transient( 'dibs_payment_id_' . $payment_id, $payment_id, 15 * MINUTE_IN_SECONDS );

		return $response;
	}
}
