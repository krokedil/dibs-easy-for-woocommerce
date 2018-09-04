<?php
/**
 * DIBS Easy for WooCommerce
 *
 * @package WC_Dibs_Easy
 *
 * @wordpress-plugin
 * Plugin Name:     		DIBS Easy for WooCommerce
 * Plugin URI:      		https://krokedil.se/dibs/
 * Description:     		Extends WooCommerce. Provides a <a href="http://www.dibspayment.com/" target="_blank">DIBS Easy</a> checkout for WooCommerce.
 * Version:         		1.4.1
 * Author:          		Krokedil
 * Author URI:      		https://krokedil.se/
 * Developer:       		Krokedil
 * Developer URI:   		https://krokedil.se/
 * Text Domain:     		dibs-easy-for-woocommerce
 * Domain Path:     		/languages
 * WC requires at least:	3.0.0
 * WC tested up to: 		3.4.5
 * Copyright:       		© 2017-2018 Krokedil Produktionsbyrå AB.
 * License:         		GNU General Public License v3.0
 * License URI:     		http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Required minimums and constants
 */
define( 'WC_DIBS_VERSION', '1.4.1' );
define( 'WC_DIBS__URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
define( 'WC_DIBS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

if ( ! class_exists( 'DIBS_Easy' ) ) {
	class DIBS_Easy {

		public static $log = '';

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
			// Checkout fields process
			add_filter( 'woocommerce_checkout_fields', array( $this, 'unrequire_fields' ), 99 );
			add_filter( 'woocommerce_checkout_posted_data', array( $this, 'unrequire_posted_data' ), 99 );
		}
		// Include the classes and enqueue the scripts.
		public function init() {
			include_once( plugin_basename( 'classes/class-dibs-get-wc-cart.php' ) );
			include_once( plugin_basename( 'classes/class-dibs-ajax-calls.php' ) );
			include_once( plugin_basename( 'classes/class-dibs-post-checkout.php' ) );
			include_once( plugin_basename( 'classes/class-dibs-order-submission-failure.php' ) );
			include_once( plugin_basename( 'classes/class-dibs-admin-notices.php' ) );
			include_once( plugin_basename( 'classes/class-dibs-api-callbacks.php' ) );
			include_once( plugin_basename( 'classes/class-dibs-templates.php' ) );
			include_once( plugin_basename( 'includes/dibs-country-converter-functions.php' ) );
			include_once( plugin_basename( 'includes/dibs-checkout-functions.php' ) );
			
			load_plugin_textdomain( 'dibs-easy-for-woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
			
			$this->init_gateway();
			include_once( plugin_basename( 'classes/class-dibs-requests.php' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
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
				
				if( isset( $_GET['dibs-payment-id'] ) ) {
					$dibs_payment_id = $_GET['dibs-payment-id'];
				} else {
					$dibs_payment_id = null;
				}

				if( isset( $_GET['paymentId'] ) ) {
					$paymentId = $_GET['paymentId'];
				} else {
					$paymentId = null;
				}

				if( WC()->session->get( 'dibs_payment_id' ) ) {
					$checkout_initiated = 'yes';
				} else {
					$checkout_initiated = 'no';
				}

				wp_enqueue_script( 'dibs-script', $script_url, array( 'jquery' ) );
				wp_register_script( 'checkout', plugins_url( '/assets/js/checkout.js', __FILE__ ), array( 'jquery' ), WC_DIBS_VERSION );
				wp_localize_script( 'checkout', 'wc_dibs_easy', array(
					'dibs_payment_id' 					=> $dibs_payment_id,
					'paymentId' 						=> $paymentId,
					'checkout_initiated' 				=> $checkout_initiated,
					'update_checkout_url'   			=> WC_AJAX::get_endpoint( 'update_checkout' ),
					'customer_adress_updated_url'   	=> WC_AJAX::get_endpoint( 'customer_adress_updated' ),
					'get_order_data_url'   				=> WC_AJAX::get_endpoint( 'get_order_data' ),
					'dibs_add_customer_order_note_url'  => WC_AJAX::get_endpoint( 'dibs_add_customer_order_note' ),
					'change_payment_method_url'   		=> WC_AJAX::get_endpoint( 'change_payment_method' ),
					'ajax_on_checkout_error_url'   		=> WC_AJAX::get_endpoint( 'ajax_on_checkout_error' ),
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
		
		/**
		 * Adds plugin action links
		 *
		 * @since 1.0.4
		 */
		public function plugin_action_links( $links ) {

			$plugin_links = array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=dibs_easy' ) . '">' . __( 'Settings', 'dibs-easy-for-woocommerce' ) . '</a>',
				'<a href="http://docs.krokedil.com/documentation/dibs-easy-for-woocommerce/">' . __( 'Docs', 'dibs-easy-for-woocommerce' ) . '</a>',
				'<a href="https://krokedil.se/support/">' . __( 'Support', 'dibs-easy-for-woocommerce' ) . '</a>',
			);
			return array_merge( $plugin_links, $links );
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

		/**
		 * When checking out using Easy, we need to make sure none of the WooCommerce are required, in case DIBS
		 * does not return info for some of them.
		 *
		 * @param array $fields WooCommerce checkout fields.
		 *
		 * @return mixed
		 */
		public function unrequire_fields( $fields ) {
			if ( 'dibs_easy' === WC()->session->get( 'chosen_payment_method' ) ) {
				foreach ( $fields as $fieldset_key => $fieldset ) {
					foreach ( $fieldset as $key => $field ) {
						$fields[ $fieldset_key ][ $key ]['required']        = '';
						$fields[ $fieldset_key ][ $key ]['wooccm_required'] = '';
					}
				}
			}
			return $fields;
		}

		/**
		 * Makes sure there's no empty data sent for validation.
		 *
		 * @param array $data Posted data.
		 *
		 * @return mixed
		 */
		public function unrequire_posted_data( $data ) {
			if ( 'dibs_easy' === WC()->session->get( 'chosen_payment_method' ) ) {
				foreach ( $data as $key => $value ) {
					if ( '' === $value ) {
						unset( $data[ $key ] );
					}
				}
			}
			return $data;
		}

		public static function log( $message ) {
			$dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
			if ( 'yes' === $dibs_settings['debug_mode'] ) {
				if ( empty( self::$log ) ) {
					self::$log = new WC_Logger();
				}
				self::$log->add( 'dibs_easy', $message );
			}
		}
	}
	$dibs_easy = new DIBS_Easy();
}// End if().
