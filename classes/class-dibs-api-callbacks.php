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
 * DIBS_Api_Callbacks class.
 *
 * @since 1.4.0
 *
 * Class that handles DIBS API callbacks.
 */
class DIBS_Api_Callbacks {

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
		add_action( 'dibs_payment_created_callback', array( $this, 'execute_dibs_payment_created_callback' ), 10, 2 );

	}

	/**
	 * Handle scheduling of payment completed webhook.
	 */
	public function payment_created_scheduler() {
		$dibs_payment_created_callback = filter_input( INPUT_GET, 'dibs-payment-created-callback', FILTER_SANITIZE_STRING );
		if ( ! empty( $dibs_payment_created_callback ) && '1' === $dibs_payment_created_callback ) {

			$post_body = file_get_contents( 'php://input' );
			$data      = json_decode( $post_body, true );

			// Order id is set to '' for now.
			$order_id = '';

			wp_schedule_single_event( time() + 120, 'dibs_payment_created_callback', array( $data, $order_id ) );
		}
	}

	/**
	 * Handle execution of payment created cronjob.
	 *
	 * @param array  $data order data returned from Nets.
	 * @param string $order_id WC order id.
	 */
	public function execute_dibs_payment_created_callback( $data, $order_id = '' ) {

		Nets_Easy()->logger->log( 'Payment created API callback. Response data:' . wp_json_encode( $data ) );
		if ( empty( $order_id ) ) {
			// We're missing Order ID in callback. Try to get it via query by internal reference.
			$order_id = $this->get_order_id_from_payment_id( $data['data']['paymentId'] );
		}

		if ( empty( $order_id ) ) {
			Nets_Easy()->logger->log( 'No coresponding order ID was found for Payment ID ' . $data['data']['paymentId'] );
			return;
		}

		$order = wc_get_order( $order_id );

		// Maybe abort the callback (if the order already has been processed in Woo).
		if ( ! empty( $order->get_date_paid() ) ) {
			Nets_Easy()->logger->log( 'Aborting Payment created API callback. Order ' . $order->get_order_number() . '(order ID ' . $order_id . ') already processed.' );
		} else {
			Nets_Easy()->logger->log( 'Order status not set correctly for order ' . $order->get_order_number() . ' during checkout process. Setting order status to Processing/Completed in API callback.' );
			wc_dibs_confirm_dibs_order( $order_id );
			$this->check_order_totals( $order, $data );
		}
	}

	/**
	 * Try to retreive order_id from DIBS transaction id.
	 *
	 * @param string $payment_id Nets transaction id.
	 */
	public function get_order_id_from_payment_id( $payment_id ) {

		if ( empty( $payment_id ) ) {
			return false;
		}

		// Let's check so the internal reference doesn't already exist in an existing order.
		$query  = new WC_Order_Query(
			array(
				'limit'          => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'return'         => 'ids',
				'payment_method' => 'dibs_easy',
				'date_created'   => '>' . ( time() - MONTH_IN_SECONDS ),
			)
		);
		$orders = $query->get_orders();

		$order_id_match = '';
		foreach ( $orders as $order_id ) {

			$order_payment_id = get_post_meta( $order_id, '_dibs_payment_id', true );

			if ( $order_payment_id === $payment_id ) {
				$order_id_match = $order_id;
				Nets_Easy()->logger->log( 'Payment ID ' . $payment_id . ' exist in order ID ' . $order_id_match );
				break;
			}
		}
		return $order_id_match;
	}

	/**
	 * Check order totals.
	 *
	 * @param object $order WC order.
	 * @param array  $dibs_order Order data from Nets.
	 */
	public function check_order_totals( $order, $dibs_order ) {

		$order_totals_match = true;

		// Check order total and compare it with Woo.
		$woo_order_total  = intval( round( $order->get_total() * 100 ) );
		$dibs_order_total = $dibs_order['data']['order']['amount']['amount'];

		if ( $woo_order_total > $dibs_order_total && ( $woo_order_total - $dibs_order_total ) > 30 ) {
			/* Translators: Nets order total. */
			$order->update_status( 'on-hold', sprintf( __( 'Order needs manual review. WooCommerce order total and Nets order total do not match. Nets order total: %s.', 'dibs-easy-for-woocommerce' ), $dibs_order_total ) );
			Nets_Easy()->logger->log( 'Order total missmatch in order:' . $order->get_order_number() . '. Woo order total: ' . $woo_order_total . '. Nets order total: ' . $dibs_order_total );
			$order_totals_match = false;
		} elseif ( $dibs_order_total > $woo_order_total && ( $dibs_order_total - $woo_order_total ) > 30 ) {
			/* Translators: Nets order total. */
			$order->update_status( 'on-hold', sprintf( __( 'Order needs manual review. WooCommerce order total and Nets order total do not match. Nets order total: %s.', 'dibs-easy-for-woocommerce' ), $dibs_order_total ) );
			Nets_Easy()->logger->log( 'Order total missmatch in order:' . $order->get_order_number() . '. Woo order total: ' . $woo_order_total . '. Nets order total: ' . $dibs_order_total );
			$order_totals_match = false;
		}

		return $order_totals_match;

	}
}
DIBS_Api_Callbacks::get_instance();
