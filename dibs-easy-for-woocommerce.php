<?php
/**
 * DIBS Easy for WooCommerce
 *
 * @package WC_Dibs_Easy
 *
 * @wordpress-plugin
 * Plugin Name:     DIBS Easy for WooCommerce
 * Plugin URI:      https://krokedil.se/
 * Description:     Extends WooCommerce. Provides a <a href="http://www.dibspayment.com/" target="_blank">DIBS Easy</a> checkout for WooCommerce.
 * Version:         0.3.2
 * Author:          Krokedil
 * Author URI:      https://woocommerce.com/
 * Developer:       Krokedil
 * Developer URI:   http://krokedil.com/
 * Text Domain:     dibs-easy-for-woocommerce
 * Domain Path:     /languages
 * Copyright:       © 2009-2017 WooCommerce.
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( ! class_exists( 'DIBS_Easy' ) ) {
	class DIBS_Easy {
		public $dibs_settings;
		public function __construct() {
			$this->dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
			add_action( 'woocommerce_email_after_order_table', array( $this, 'email_extra_information' ), 10, 3 );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			define( 'DIR_NAME' , dirname( __FILE__ ) );
			// Register custom order status
			add_action( 'init', array( $this, 'register_dibs_incomplete_order_status' ) );
			add_filter( 'wc_order_statuses', array( $this, 'add_dibs_incomplete_to_order_statuses' ) );
			add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'dibs_incomplete_payment_complete' ) );
			add_filter( 'woocommerce_valid_order_statuses_for_payment', array( $this, 'dibs_incomplete_payment_complete' ) );
			// Send mails with the custom order status
			add_filter( 'woocommerce_email_actions', array( $this, 'wc_add_dibs_incomplete_email_actions' ) );
			add_action( 'woocommerce_order_status_dibs-incomplete_to_processing_notification', array( $this, 'wc_dibs_incomplete_trigger' ) );

			// Remove the storefront sticky checkout.
			add_action( 'wp_enqueue_scripts', array( $this, 'jk_remove_sticky_checkout' ), 99 );
			
			// Cart page error notice
			add_action( 'woocommerce_before_cart', array( $this, 'add_error_notice_to_cart_page' ) );
		}
		// Include the classes and enqueue the scripts.
		public function init() {
			include_once( plugin_basename( 'classes/class-dibs-get-wc-cart.php' ) );
			include_once( plugin_basename( 'classes/class-dibs-ajax-calls.php' ) );
			include_once( plugin_basename( 'classes/class-dibs-post-checkout.php' ) );
			
			load_plugin_textdomain( 'dibs-easy-for-woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
			
			$this->init_gateway();
			include_once( plugin_basename( 'classes/class-dibs-requests.php' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		}

		// Include DIBS Gateway if WC_Payment_Gateway exist
		public function init_gateway() {
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}
			include_once( plugin_basename( 'classes/class-dibs-easy-gateway.php' ) );

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_dibs_easy' ) );
		}

		// Load the needed JS scripts.
		public function load_scripts() {
			wp_enqueue_script( 'jquery' );
			if ( is_checkout() ) {
				$testmode      = 'yes' === $this->dibs_settings['test_mode'];
				$script_url    = $testmode ? 'https://test.checkout.dibspayment.eu/v1/checkout.js?v=1' : 'https://checkout.dibspayment.eu/v1/checkout.js?v=1';

				wp_enqueue_script( 'dibs-script', $script_url, array( 'jquery' ) );
				wp_register_script( 'checkout', plugins_url( '/assets/js/checkout.js', __FILE__ ), array( 'jquery' ) );
				wp_localize_script( 'checkout', 'wc_dibs_easy', array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				) );
				wp_enqueue_script( 'checkout' );

				// Load stylesheet for the checkout page
				wp_register_style(
					'dibs_style',
					plugin_dir_url( __FILE__ ) . '/assets/css/style.css'
				);
				wp_enqueue_style( 'dibs_style' );
			}
		}

		// Add DIBS Easy gateway to WooCommerce Admin interface
		function add_dibs_easy( $methods ) {
			$methods[] = 'DIBS_Easy_Gateway';

			return $methods;
		}

		// Add custom order status
		public function register_dibs_incomplete_order_status() {
			/* Add this later with Debug option
			if ( 'yes' == $this->debug ) {
				$show_in_admin_status_list = true;
			} else {
				$show_in_admin_status_list = false;
			} */
			register_post_status( 'wc-dibs-incomplete', array(
				'label'                     => 'DIBS incomplete',
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
				'label_count'               => _n_noop( 'DIBS incomplete <span class="count">(%s)</span>', 'DIBS incomplete <span class="count">(%s)</span>' ),
			) );
		}
		public function dibs_incomplete_payment_complete( $order_statuses ) {
			$order_statuses[] = 'dibs-incomplete';
			return $order_statuses;
		}

		public function add_dibs_incomplete_to_order_statuses( $order_statuses ) {
			// Add this status only if not in account page (so it doesn't show in My Account list of orders)
			if ( ! is_account_page() ) {
				$order_statuses['wc-dibs-incomplete'] = 'Incomplete DIBS Easy order';
			}
			return $order_statuses;
		}

		public function wc_add_dibs_incomplete_email_actions( $email_actions ) {
			$email_actions[] = 'woocommerce_order_status_dibs-incomplete_to_processing';
			return $email_actions;
		}

		public function wc_dibs_incomplete_trigger( $order_id ) {
			$dibs_mailer = WC()->mailer();
			$dibs_mails  = $dibs_mailer->get_emails();
			foreach ( $dibs_mails as $dibs_mail ) {
				$order = new WC_Order( $order_id );
				if ( 'new_order' == $dibs_mail->id || 'customer_processing_order' == $dibs_mail->id ) {
					$dibs_mail->trigger( $order->id );
				}
			}
		}

		public function jk_remove_sticky_checkout() {
			wp_dequeue_script( 'storefront-sticky-payment' );
		}
		
		public function email_extra_information( $order, $sent_to_admin, $plain_text = false ) {
			$order_id     = $order->get_id();
			$gateway_used = get_post_meta( $order_id, '_payment_method', true );
			if ( 'dibs_easy' == $gateway_used ) {
				$payment_id    	= get_post_meta( $order_id, '_dibs_payment_id', true );
				$customer_card 	= get_post_meta( $order_id, 'dibs_customer_card', true );
				$payment_type 	= get_post_meta( $order_id, 'dibs_payment_type', true );
				$order_date = wc_format_datetime( $order->get_date_created() );
				$dibs_settings 	= $this->dibs_settings;
				
				if ( $dibs_settings['email_text'] ) {
						echo wpautop( wptexturize( $dibs_settings['email_text'] ) );
				}
				if ( $order_date ) {
					echo wpautop( wptexturize( __( 'Order date: ', 'dibs-easy-for-woocommerce' ) . $order_date ) );
				}
				if ( $payment_id ) {
					echo wpautop( wptexturize( __( 'DIBS Payment ID: ', 'dibs-easy-for-woocommerce' ) . $payment_id ) );
				}
				if ( $payment_type ) {
					echo wpautop( wptexturize( __( 'Payment type: ', 'dibs-easy-for-woocommerce' ) . $payment_type ) );
				}
				if ( $customer_card ) {
					echo wpautop( wptexturize( __( 'Customer card: ', 'dibs-easy-for-woocommerce' ) . $customer_card ) );
				}
			}
		}
		
		public function add_error_notice_to_cart_page() {
			if (isset($_GET['dibs-payment-id'])) {
				wc_print_notice( __( 'There was a problem paying with DIBS.', 'dibs-easy-for-woocommerce' ), 'error' );
			}
		}
	}
	$dibs_easy = new DIBS_Easy();
}// End if().
