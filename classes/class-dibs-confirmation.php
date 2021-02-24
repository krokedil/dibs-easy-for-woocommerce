<?php
/**
 * Confirmation Class file.
 *
 * @package DIBS/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * DIBS_Confirmation class.
 *
 * @since 1.17.0
 *
 * Class that handles confirmatiin of order and redirect to Thank you page.
 */
class DIBS_Confirmation {

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
	 * DIBS_Confirmation constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'confirm_order' ), 10, 2 );

	}


	/**
	 * Confirm the order in Woo before redirecting the customer to thank you page.
	 */
	public function confirm_order() {

		if ( isset( $_GET['easy_confirm'] ) && isset( $_GET['key'] ) ) { // phpcs:ignore
			$order_id = wc_get_order_id_by_order_key( sanitize_text_field( wp_unslash( $_GET['key'] ) ) ); // phpcs:ignore
			$order    = wc_get_order( $order_id );

			Nets_Easy()->logger->log( $order_id . ': Confirmation endpoint hit for order.' );

			if ( empty( $order->get_date_paid() ) ) {

				Nets_Easy()->logger->log( $order_id . ': Confirm the Nets order from the confirmation page.' );

				// Confirm the order.
				wc_dibs_confirm_dibs_order( $order_id );
				wc_dibs_unset_sessions();
			}
		}
	}
}
DIBS_Confirmation::get_instance();
