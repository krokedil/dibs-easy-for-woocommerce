<?php
/**
 * API Callback class
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Nets_Easy_Api_Callbacks class.
 *
 * @since 1.4.0
 *
 * Class that handles DIBS API callbacks.
 */
class Nets_Easy_Api_Callbacks {

	/**
	 * The reference the *Singleton* instance of this class.
	 *
	 * @var $instance
	 */
	protected static $instance;
	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return self::$instance The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * DIBS_Api_Callbacks constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_api_dibs_api_callbacks', array( $this, 'payment_created_scheduler' ) );
		add_action( 'dibs_payment_created_callback', array( $this, 'execute_dibs_payment_created_callback' ), 10, 3 );
	}

	/**
	 * Handle scheduling of payment completed webhook.
	 */
	public function payment_created_scheduler() {
		$dibs_payment_created_callback = filter_input( INPUT_GET, 'dibs-payment-created-callback', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! empty( $dibs_payment_created_callback ) && '1' === $dibs_payment_created_callback ) {

			$post_body = file_get_contents( 'php://input' );
			$data      = json_decode( $post_body, true );

			$amount       = $data['data']['order']['amount']['amount'];
			$payment_id   = $data['data']['paymentId'];
			$order_number = $data['data']['order']['reference'];

			Nets_Easy_Logger::log( 'Payment created webhook listener hit ' . wp_json_encode( $data ) );

			as_schedule_single_action( time() + 120, 'dibs_payment_created_callback', array( $payment_id, $order_number, $amount ) );
			header( 'HTTP/1.1 200 OK' );
			die();
		}
	}

	/**
	 * Handle execution of payment created cronjob.
	 *
	 * @param string $payment_id Nets payment id.
	 * @param string $order_number WC order number.
	 * @param string $amount Nets order amount.
	 */
	public function execute_dibs_payment_created_callback( $payment_id, $order_number, $amount ) {

		Nets_Easy_Logger::log( 'Execute Payment created API callback. Payment ID:' . $payment_id . '. Order number: ' . $order_number . '. Amount: ' . $amount );

		$order = nets_easy_get_order_by_purchase_id( $payment_id );

		if ( empty( $order ) ) {
			Nets_Easy_Logger::log( 'No corresponding order ID was found for Payment ID ' . $payment_id );
			return;
		}

		// Maybe abort the callback (if the order already has been processed in Woo).
		if ( ! empty( $order->get_date_paid() ) ) {
			Nets_Easy_Logger::log( 'Aborting Payment created API callback. Order ' . $order->get_order_number() . '(order ID ' . $order->get_id() . ') already processed.' );
		} else {
			Nets_Easy_Logger::log( 'Order status not set correctly for order ' . $order->get_order_number() . ' during checkout process. Setting order status to Processing/Completed in API callback.' );
			wc_dibs_confirm_dibs_order( $order->get_id() );
			$this->check_order_totals( $order, $amount );
		}
	}

	/**
	 * Check order totals.
	 *
	 * @param object $order WC order.
	 * @param string $dibs_order_total Order total amount from Nets.
	 */
	public function check_order_totals( $order, $dibs_order_total ) {

		$order_totals_match = true;

		// Check order total and compare it with Woo.
		$woo_order_total = intval( round( $order->get_total() * 100 ) );

		if ( $woo_order_total > $dibs_order_total && ( $woo_order_total - $dibs_order_total ) > 30 ) {
			/* Translators: Nets order total. */
			$order->update_status( 'on-hold', sprintf( __( 'Order needs manual review. WooCommerce order total and Nexi order total do not match. Nexi order total: %s.', 'dibs-easy-for-woocommerce' ), $dibs_order_total ) );
			Nets_Easy_Logger::log( 'Order total mismatch in order:' . $order->get_order_number() . '. Woo order total: ' . $woo_order_total . '. Nexi order total: ' . $dibs_order_total );
			$order_totals_match = false;
		} elseif ( $dibs_order_total > $woo_order_total && ( $dibs_order_total - $woo_order_total ) > 30 ) {
			/* Translators: Nets order total. */
			$order->update_status( 'on-hold', sprintf( __( 'Order needs manual review. WooCommerce order total and Nexi order total do not match. Nexi order total: %s.', 'dibs-easy-for-woocommerce' ), $dibs_order_total ) );
			Nets_Easy_Logger::log( 'Order total mismatch in order:' . $order->get_order_number() . '. Woo order total: ' . $woo_order_total . '. Nexi order total: ' . $dibs_order_total );
			$order_totals_match = false;
		}

		return $order_totals_match;
	}
}
Nets_Easy_Api_Callbacks::get_instance();
