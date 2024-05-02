<?php
/**
 * Nets_Easy_Order_Management class.
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Nets_Easy_Order_Management class.
 */
class Nets_Easy_Order_Management {

	/**
	 * $manage_orders
	 *
	 * @var string
	 */
	public $manage_orders;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$dibs_settings       = get_option( 'woocommerce_dibs_easy_settings' );
		$this->manage_orders = $dibs_settings['dibs_manage_orders'] ?? '';
		if ( 'yes' === $this->manage_orders ) {
			add_action( 'woocommerce_order_status_completed', array( $this, 'dibs_order_completed' ) );
			add_action( 'woocommerce_order_status_cancelled', array( $this, 'dibs_order_canceled' ) );
		}
	}

	/**
	 * Charge / Activate order.
	 *
	 * @param  string $order_id WooCommerce order id.
	 */
	public function dibs_order_completed( $order_id ) {

		$wc_order = wc_get_order( $order_id );

		// Check if dibs was used to make the order.
		$gateway_used = $wc_order->get_payment_method();
		if ( in_array( $gateway_used, nets_easy_all_payment_method_ids(), true ) ) {

			// Bail if the order hasn't been paid in DIBS yet.
			if ( empty( $wc_order->get_meta( '_dibs_date_paid' ) ) ) {
				return;
			}

			// Bail if we already have charged the order once in DIBS system.
			if ( $wc_order->get_meta( '_dibs_charge_id' ) ) {
				return;
			}

			$payment_type = $wc_order->get_meta( 'dibs_payment_type' );
			if ( 'A2A' === $payment_type ) {
				// This is a account to account purchase (like Swish). No activation is needed/possible.
				$dibs_payment_method = $wc_order->get_meta( 'dibs_payment_method' );
				/* Translators: Nets payment method for the order. */
				$wc_order->add_order_note( sprintf( __( 'No charge needed in Nexi system since %s is a account to account payment.', 'dibs-easy-for-woocommerce' ), $dibs_payment_method ) );
				return;
			}

			// Bail if order total is 0. Can happen for 0 value initial subscription orders.
			if ( round( 0, 2 ) === round( $wc_order->get_total(), 2 ) ) {
				/* Translators: WC order total for the order. */
				$wc_order->add_order_note( sprintf( __( 'No charge needed in Nexi system since the order total is %s.', 'dibs-easy-for-woocommerce' ), $wc_order->get_total() ) );
				return;
			}
			// Get Nexi Checkout order.
			$nets_easy_order = Nets_Easy()->api->get_nets_easy_order( $wc_order->get_meta( '_dibs_payment_id' ) );
			if ( is_wp_error( $nets_easy_order ) ) {
				$this->fetching_order_failed( $wc_order, true, $nets_easy_order->get_error_message() );
				return;
			}
			if ( $this->is_completed( $nets_easy_order, $order_id ) ) {
				return;
			}

			// Try to activate.
			$response = Nets_Easy()->api->activate_nets_easy_order( $order_id );
			if ( is_wp_error( $response ) ) {
				/**
				 * Response is WordPress error.
				 *
				 * @var string|array WP_Error $response
				 */
				$this->charge_failed( $wc_order, true, __( 'Unable to activate the order!' ) . ' ' . $response->get_error_message() );
				return;
			}
			// Translators: Nets payment charge ID.
			$wc_order->add_order_note( sprintf( __( 'Payment charged in Nexi with charge ID %s', 'dibs-easy-for-woocommerce' ), $response['chargeId'] ) ); // phpcs:ignore
			$wc_order->update_meta_data( '_dibs_charge_id', $response['chargeId'] );
			$wc_order->save();
		}
	}

	/**
	 * Cancel order.
	 *
	 * @param  string $order_id WooCommerce order id.
	 */
	public function dibs_order_canceled( $order_id ) {
		$wc_order = wc_get_order( $order_id );
		// Check if dibs was used to make the order.
		$gateway_used = $wc_order->get_payment_method();
		if ( in_array( $gateway_used, nets_easy_all_payment_method_ids(), true ) ) {

			// Don't do this if the order hasn't been paid in DIBS.
			if ( empty( $wc_order->get_meta( '_dibs_date_paid' ) ) ) {
				return;
			}

			$nets_easy_order = Nets_Easy()->api->get_nets_easy_order( $wc_order->get_meta( '_dibs_payment_id' ) );
			if ( is_wp_error( $nets_easy_order ) ) {
				$this->fetching_order_failed( $wc_order, true, $nets_easy_order->get_error_message() );
				return;
			}

			if ( $this->is_canceled( $nets_easy_order, $order_id ) ) {
				return;
			}

			$response = Nets_Easy()->api->cancel_nets_easy_order( $order_id );
			if ( is_wp_error( $response ) ) {
				/**
				 * Response is WordPress error.
				 *
				 * @var string|array WP_Error $response
				 */
				$this->cancel_failed( $wc_order, true, __( 'There was a problem canceling the order in Nexi' ) . ' ' . $response->get_error_message() );
				return;
			}
			$wc_order = wc_get_order( $order_id );
			$wc_order->add_order_note( sprintf( __( 'Order has been canceled in Nexi', 'dibs-easy-for-woocommerce' ) ) );
		}
	}

	/**
	 * Function to handle a failed order.
	 *
	 * @param  WC_Order $order WooCommerce order.
	 * @param  bool     $fail Failed or not.
	 * @param  string   $message Message for the order note.
	 */
	public function charge_failed( $order, $fail = true, $message = 'Payment failed in Nexi' ) {
		/* Translators: Nets message. */
		$order->add_order_note( sprintf( __( 'Nexi Error: %s', 'dibs-easy-for-woocommerce' ), $message ) );
		if ( true === $fail ) {
			$order->update_status( apply_filters( 'dibs_easy_failed_charge_status', 'on-hold', $order ) );
			$order->save();
		}
	}

	/**
	 * Function to handle a failed (cancel) order.
	 *
	 * @param  WC_Order $order WooCommerce order.
	 * @param  bool     $fail Failed or not.
	 * @param  string   $message Message for the order note.
	 *
	 * @return void
	 */
	public function cancel_failed( $order, $fail = true, $message = 'Payment failed in Nexi' ) {
		/* Translators: Nets message. */
		$order->add_order_note( sprintf( __( 'Nexi Error: %s', 'dibs-easy-for-woocommerce' ), $message ) );
		if ( true === $fail ) {
			$order->update_status( apply_filters( 'dibs_easy_failed_cancel_status', 'on-hold', $order ) );
			$order->save();
		}
	}

	/**
	 * Function to handle a failed (fetch) order.
	 *
	 * @param  WC_Order $order WooCommerce order.
	 * @param  bool     $fail Failed or not.
	 * @param  string   $message Message for the order note.
	 */
	public function fetching_order_failed( $order, $fail = true, $message = 'Unable to get the order' ) {
		/* Translators: Nets message. */
		$order->add_order_note( sprintf( __( 'Nexi Error: %s', 'dibs-easy-for-woocommerce' ), $message ) );
		if ( true === $fail ) {
			$order->update_status( apply_filters( 'dibs_easy_failed_get_status', 'on-hold', $order ) );
			$order->save();
		}
	}



	/**
	 * Check if order is already canceled, and sets post meta.
	 *
	 * @param array $nets_easy_order The Nexi Checkout order.
	 * @param int   $order_id The WooCommerce order id.
	 *
	 * @return bool
	 */
	public function is_canceled( $nets_easy_order, $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! is_wp_error( $nets_easy_order ) && isset( $nets_easy_order['payment']['summary'] ) ) {
			$canceled_amount = $nets_easy_order['payment']['summary']['cancelledAmount'] ?? false;
			// If cancelledAmount exists, update the post meta value.
			if ( $canceled_amount ) {
				$order->update_meta_data( '_dibs_canceled_amount_id', $canceled_amount );
				// Translators: 1. Nexi Checkout Payment id 2. Payment type  3.Charge id.
				$order->add_order_note( sprintf( __( 'Payment canceled in Nexi ( Portal ) with Payment ID %1$s. Payment type - %2$s. Charge ID %3$s.', 'dibs-easy-for-woocommerce' ), $nets_easy_order['payment']['paymentId'], $nets_easy_order['payment']['paymentDetails']['paymentMethod'], $canceled_amount ) );
				$order->save();
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if order is already completed, and sets post meta.
	 *
	 * @param array $nets_easy_order The Nexi Checkout order.
	 * @param int   $order_id The WooCommerce order id.
	 *
	 * @return bool
	 */
	public function is_completed( $nets_easy_order, $order_id ) {
		$wc_order = wc_get_order( $order_id );
		if ( is_wp_error( $nets_easy_order ) || empty( $wc_order ) || empty( $nets_easy_order['payment']['charges'] ?? false ) ) {
			return false;
		}

		$dibs_charge_id = $nets_easy_order['payment']['charges'][0]['chargeId'] ?? false;
		if ( empty( $dibs_charge_id ) ) {
			return false;
		}

		$charge_id = $nets_easy_order['payment']['charges'][0]['chargeId'];
		$wc_order->update_meta_data( '_dibs_charge_id', $charge_id );
		$wc_order->save();

		// Translators: 1. Nexi Checkout Payment id 2. Payment type  3.Charge id.
		$wc_order->add_order_note( sprintf( __( 'Payment charged in Nexi ( Portal ) with Payment ID %1$s. Payment type - %2$s. Charge ID %3$s.', 'dibs-easy-for-woocommerce' ), $nets_easy_order['payment']['paymentId'], $nets_easy_order['payment']['paymentDetails']['paymentMethod'], $dibs_charge_id ) );

		return true;
	}
}
