<?php
/**
 * Adds the possibility to add Nexi Checkout data to the end of order confirmation emails.
 *
 * @package Dibs_Easy_For_WooCommerce/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Nets_Easy_Email' ) ) :
	/**
	 * The class for email handling for Nexi Checkout.
	 */
	class Nets_Easy_Email {

		/**
		 * Class constructor.
		 */
		public function __construct() {
			add_action( 'woocommerce_email_after_order_table', array( $this, 'email_extra_information' ), 10, 3 );
		}

		/**
		 * Add Nexi Checkout related information to WooCommerce order emails.
		 *
		 * @param  object $order WooCommerce order.
		 * @param  bool   $sent_to_admin Email to admin or not.
		 * @param  bool   $plain_text Email html or text format.
		 *
		 * @return void
		 */
		public function email_extra_information( $order, $sent_to_admin, $plain_text = false ) {
			$settings                = get_option( 'woocommerce_dibs_easy_settings' );
			$email_nets_payment_data = $settings['email_nets_payment_data'] ?? 'yes';
			$order_id                = $order->get_id();
			$gateway_used            = $order->get_payment_method();

			if ( in_array( $gateway_used, nets_easy_all_payment_method_ids(), true ) ) {
				$payment_id     = $order->get_meta( '_dibs_payment_id' );
				$customer_card  = $order->get_meta( 'dibs_customer_card' );
				$payment_method = $order->get_meta( 'dibs_payment_method' );
				$order_date     = wc_format_datetime( $order->get_date_created() );

				if ( $settings['email_text'] ) {
					echo wp_kses_post( wpautop( wptexturize( $settings['email_text'] ) ) );
				}
				if ( 'yes' === $email_nets_payment_data ) {
					if ( $payment_id ) {
						echo wp_kses_post( wpautop( wptexturize( __( 'Nets Payment ID: ', 'dibs-easy-for-woocommerce' ) . $payment_id ) ) );
					}
					if ( $payment_method ) {
						echo wp_kses_post( wpautop( wptexturize( __( 'Payment method: ', 'dibs-easy-for-woocommerce' ) . $payment_method ) ) );
					}
					if ( $customer_card ) {
						echo wp_kses_post( wpautop( wptexturize( __( 'Customer card: ', 'dibs-easy-for-woocommerce' ) . $customer_card ) ) );
					}
				}
			}
		}
	}
	new Nets_Easy_Email();
endif;
