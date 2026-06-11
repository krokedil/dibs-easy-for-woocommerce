<?php
/**
 * AJAX handlers for the Express Button flow.
 *
 * @package Krokedil\NexiCheckout\ExpressButton
 */

namespace Krokedil\Nexi\ExpressButton;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and handles the three Express Button AJAX endpoints.
 *
 * Endpoints (all nopriv):
 *   - nexi_express_create_payment   – creates a Nexi Express payment from a product.
 *   - nexi_express_shipping_update  – recalculates shipping for a given country/postcode.
 *   - nexi_express_payment_complete – creates a WC order after Nexi confirms payment.
 */
class Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$events = [
			'nexi_express_create_payment',
			'nexi_express_shipping_update',
			'nexi_express_payment_complete',
		];

		foreach ( $events as $event ) {
			add_action( 'wp_ajax_woocommerce_' . $event, [ $this, $event ] );
			add_action( 'wp_ajax_nopriv_woocommerce_' . $event, [ $this, $event ] );
			add_action( 'wc_ajax_' . $event, [ $this, $event ] );
		}
	}

	/**
	 * Creates a Nexi Express payment for a single product.
	 *
	 * Expects POST: nonce, product_id, quantity.
	 * Returns JSON: { paymentId: string }.
	 *
	 * @return void
	 */
	public function nexi_express_create_payment(): void {
		$this->verify_nonce();

		$product_id = intval( filter_input( INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT ) );
		$quantity   = max( 1, intval( filter_input( INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT ) ) );

		if ( ! $product_id ) {
			wp_send_json_error( 'Missing product_id.' );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_purchasable() ) {
			wp_send_json_error( 'Product not purchasable.' );
		}

		$items = $this->build_product_items( $product, $quantity );
		$total = $this->sum_gross( $items );

		$body = [
			'order'    => [
				'items'     => $items,
				'amount'    => $total,
				'currency'  => get_woocommerce_currency(),
				'reference' => (string) $product->get_id(),
			],
			'checkout' => [
				'integrationType'            => 'Express',
				'termsUrl'                   => wc_get_page_permalink( 'terms' ),
				'merchantHandlesShippingCost' => true,
				'shipping'                   => [
					'countries'                  => [],
					'merchantHandlesShippingCost' => true,
				],
			],
			'notifications' => \Nets_Easy_Notification_Helper::get_notifications(),
		];

		// Store product context in session so shipping_update and payment_complete can use it.
		WC()->session->set(
			'nexi_express_product',
			[
				'product_id' => $product_id,
				'quantity'   => $quantity,
			]
		);

		$request  = new \Nets_Easy_Request_Create_Express_Order( [ 'body' => $body ] );
		$response = $request->request();

		if ( is_wp_error( $response ) ) {
			\Nets_Easy_Logger::log( '[Express] Create payment error: ' . $response->get_error_message() );
			wp_send_json_error( $response->get_error_message() );
		}

		$payment_id = $response['paymentId'] ?? '';
		if ( empty( $payment_id ) ) {
			wp_send_json_error( 'Empty paymentId from Nexi.' );
		}

		WC()->session->set( 'nexi_express_payment_id', $payment_id );
		\Nets_Easy_Logger::log( "[Express] Payment created: $payment_id" );

		wp_send_json_success( [ 'paymentId' => $payment_id ] );
	}

	/**
	 * Recalculates shipping cost for a country/postcode and updates the Nexi payment.
	 *
	 * Expects POST: nonce, payment_id, country_code (ISO-2), postal_code.
	 * Returns JSON: { amount: int } (total in minor units).
	 *
	 * @return void
	 */
	public function nexi_express_shipping_update(): void {
		$this->verify_nonce();

		$payment_id  = sanitize_text_field( filter_input( INPUT_POST, 'payment_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
		$country     = sanitize_text_field( filter_input( INPUT_POST, 'country_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
		$postal_code = sanitize_text_field( filter_input( INPUT_POST, 'postal_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );

		if ( ! $payment_id || ! $country ) {
			wp_send_json_error( 'Missing required fields.' );
		}

		$session_payment_id = WC()->session->get( 'nexi_express_payment_id' );
		if ( $payment_id !== $session_payment_id ) {
			wp_send_json_error( 'Payment ID mismatch.' );
		}

		// Update customer location so WC calculates the correct shipping.
		WC()->customer->set_shipping_country( $country );
		WC()->customer->set_billing_country( $country );
		if ( $postal_code ) {
			WC()->customer->set_shipping_postcode( $postal_code );
			WC()->customer->set_billing_postcode( $postal_code );
		}
		WC()->customer->save();

		$product_ctx = WC()->session->get( 'nexi_express_product' );
		if ( empty( $product_ctx ) ) {
			wp_send_json_error( 'Session context missing.' );
		}

		$product = wc_get_product( $product_ctx['product_id'] );
		if ( ! $product ) {
			wp_send_json_error( 'Product not found.' );
		}

		$items    = $this->build_product_items( $product, $product_ctx['quantity'] );
		$shipping = $this->get_cheapest_shipping( $product, $product_ctx['quantity'] );
		if ( $shipping ) {
			$items[] = $shipping;
		}

		$total = $this->sum_gross( $items );

		// Update the Nexi payment with new items/total.
		$update_body = [
			'amount' => $total,
			'items'  => $items,
		];
		$request  = new \Nets_Easy_Request_Update_Express_Order(
			[
				'payment_id' => $payment_id,
				'body'       => $update_body,
			]
		);
		$response = $request->request();

		if ( is_wp_error( $response ) ) {
			\Nets_Easy_Logger::log( '[Express] Shipping update error: ' . $response->get_error_message() );
			wp_send_json_error( $response->get_error_message() );
		}

		// Store chosen shipping for use in order creation.
		WC()->session->set( 'nexi_express_shipping', $shipping );

		wp_send_json_success( [ 'amount' => $total ] );
	}

	/**
	 * Creates a WooCommerce order after Nexi confirms the payment.
	 *
	 * Expects POST: nonce, payment_id.
	 * Returns JSON: { redirect: string } (order received URL).
	 *
	 * @return void
	 */
	public function nexi_express_payment_complete(): void {
		$this->verify_nonce();

		$payment_id = sanitize_text_field( filter_input( INPUT_POST, 'payment_id', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );
		if ( ! $payment_id ) {
			wp_send_json_error( 'Missing payment_id.' );
		}

		$session_payment_id = WC()->session->get( 'nexi_express_payment_id' );
		if ( $payment_id !== $session_payment_id ) {
			wp_send_json_error( 'Payment ID mismatch.' );
		}

		$order_handler = new OrderHandler();
		$result        = $order_handler->create_order( $payment_id );

		if ( is_wp_error( $result ) ) {
			\Nets_Easy_Logger::log( '[Express] Order creation error: ' . $result->get_error_message() );
			wp_send_json_error( $result->get_error_message() );
		}

		// Clean up session.
		WC()->session->set( 'nexi_express_payment_id', null );
		WC()->session->set( 'nexi_express_product', null );
		WC()->session->set( 'nexi_express_shipping', null );

		wp_send_json_success( [ 'redirect' => $result ] );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Verifies the nexi_express nonce; dies on failure.
	 *
	 * @return void
	 */
	private function verify_nonce(): void {
		$nonce = sanitize_key( $_POST['nonce'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! wp_verify_nonce( $nonce, 'nexi_express' ) ) {
			wp_send_json_error( 'bad_nonce' );
			exit;
		}
	}

	/**
	 * Builds Nexi order items array for a single product.
	 *
	 * @param \WC_Product $product  The product.
	 * @param int         $quantity Purchase quantity.
	 * @return array
	 */
	private function build_product_items( \WC_Product $product, int $quantity ): array {
		$price     = (float) wc_get_price_including_tax( $product );
		$unit_net  = (float) wc_get_price_excluding_tax( $product );
		$tax_class = $product->get_tax_class();

		$_tax      = new \WC_Tax();
		$tmp_rates = $_tax->get_rates( $tax_class );
		$vat       = array_shift( $tmp_rates );
		$tax_rate  = isset( $vat['rate'] ) ? (int) round( $vat['rate'] * 100 ) : 0;

		$unit_tax_amount  = intval( round( ( $price - $unit_net ) * 100 ) );
		$unit_price_minor = intval( round( $unit_net * 100 ) );
		$gross_total      = intval( round( $price * 100 ) ) * $quantity;
		$net_total        = $unit_price_minor * $quantity;
		$total_tax        = $unit_tax_amount * $quantity;

		$sku = $product->get_sku() ?: (string) $product->get_id();
		$sku = substr( $sku, 0, 32 );

		return [
			[
				'reference'        => $sku,
				'name'             => wc_dibs_clean_name( $product->get_name() ),
				'quantity'         => $quantity,
				'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
				'unitPrice'        => $unit_price_minor,
				'taxRate'          => $tax_rate,
				'taxAmount'        => $total_tax,
				'grossTotalAmount' => $gross_total,
				'netTotalAmount'   => $net_total,
			],
		];
	}

	/**
	 * Returns a Nexi order item for the cheapest available shipping method,
	 * or null if no shipping is available.
	 *
	 * @param \WC_Product $product  The product being purchased.
	 * @param int         $quantity Quantity.
	 * @return array|null
	 */
	private function get_cheapest_shipping( \WC_Product $product, int $quantity ): ?array {
		// Build a temporary package to get rates for the current customer location.
		$package = [
			'contents'        => [
				[
					'product_id' => $product->get_id(),
					'data'       => $product,
					'quantity'   => $quantity,
				],
			],
			'contents_cost'   => (float) wc_get_price_including_tax( $product ) * $quantity,
			'applied_coupons' => [],
			'destination'     => [
				'country'   => WC()->customer->get_shipping_country(),
				'state'     => WC()->customer->get_shipping_state(),
				'postcode'  => WC()->customer->get_shipping_postcode(),
				'city'      => WC()->customer->get_shipping_city(),
				'address'   => '',
				'address_2' => '',
			],
		];

		$rates = WC()->shipping()->calculate_shipping_for_package( $package );
		if ( empty( $rates['rates'] ) ) {
			return null;
		}

		// Pick the cheapest method.
		$cheapest = null;
		foreach ( $rates['rates'] as $rate ) {
			if ( null === $cheapest || $rate->cost < $cheapest->cost ) {
				$cheapest = $rate;
			}
		}

		if ( null === $cheapest ) {
			return null;
		}

		$tax_amount = (int) round( array_sum( $cheapest->taxes ) * 100 );
		$net_cost   = (int) round( $cheapest->cost * 100 );
		$tax_rate   = $net_cost > 0 ? (int) round( ( array_sum( $cheapest->taxes ) / $cheapest->cost ) * 10000, 2 ) : 0;

		return [
			'reference'        => 'shipping|' . $cheapest->id,
			'name'             => wc_dibs_clean_name( $cheapest->label ),
			'quantity'         => 1,
			'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
			'unitPrice'        => $net_cost,
			'taxRate'          => $tax_rate,
			'taxAmount'        => $tax_amount,
			'grossTotalAmount' => $net_cost + $tax_amount,
			'netTotalAmount'   => $net_cost,
		];
	}

	/**
	 * Sums grossTotalAmount across all items.
	 *
	 * @param array $items Nexi order items.
	 * @return int
	 */
	private function sum_gross( array $items ): int {
		return (int) array_sum( array_column( $items, 'grossTotalAmount' ) );
	}
}
