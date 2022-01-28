<?php // phpcs:ignore
/**
 * Nets Easy for WooCommerce
 *
 * @package WC_Dibs_Easy
 *
 * @wordpress-plugin
 * Plugin Name:             Nets Easy for WooCommerce
 * Plugin URI:              https://krokedil.se/produkt/nets-easy/
 * Description:             Extends WooCommerce. Provides a <a href="http://www.dibspayment.com/" target="_blank">Nets Easy</a> checkout for WooCommerce.
 * Version:                 1.25.1
 * Author:                  Krokedil
 * Author URI:              https://krokedil.se/
 * Developer:               Krokedil
 * Developer URI:           https://krokedil.se/
 * Text Domain:             dibs-easy-for-woocommerce
 * Domain Path:             /languages
 * WC requires at least:    4.0.0
 * WC tested up to:         6.1.0
 * Copyright:               © 2017-2022 Krokedil AB.
 * License:                 GNU General Public License v3.0
 * License URI:             http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Required minimums and constants
 */
define( 'WC_DIBS_EASY_VERSION', '1.25.1' );
define( 'WC_DIBS__URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
define( 'WC_DIBS_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'DIBS_API_LIVE_ENDPOINT', 'https://api.dibspayment.eu/v1/' );
define( 'DIBS_API_TEST_ENDPOINT', 'https://test.api.dibspayment.eu/v1/' );

if ( ! class_exists( 'DIBS_Easy' ) ) {
	/**
	 * Class DIBS_Easy
	 */
	class DIBS_Easy {

		/**
		 * The reference the *Singleton* instance of this class.
		 *
		 * @var $instance
		 */
		protected static $instance;

		/**
		 * Reference to dibs_settings.
		 *
		 * @var $dibs_settings
		 */
		public $dibs_settings;

		/**
		 * DIBS_Easy constructor.
		 */
		public function __construct() {
			$this->dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
			$this->checkout_flow = ( isset( $this->dibs_settings['checkout_flow'] ) ) ? $this->dibs_settings['checkout_flow'] : 'embedded';
			add_action( 'woocommerce_email_after_order_table', array( $this, 'email_extra_information' ), 10, 3 );
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

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
		 * Private clone method to prevent cloning of the instance of the
		 * *Singleton* instance.
		 *
		 * @return void
		 */
		private function __clone() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Nope' ), '1.0' );
		}
		/**
		 * Private unserialize method to prevent unserializing of the *Singleton*
		 * instance.
		 *
		 * @return void
		 */
		private function __wakeup() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Nope' ), '1.0' );
		}

		/**
		 * Init the plugin after plugins_loaded so environment variables are set.
		 * Include the classes and enqueue the scripts.
		 */
		public function init() {

			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}
			if ( 'embedded' === $this->checkout_flow ) {
				include_once plugin_basename( 'classes/class-dibs-templates.php' );
			}

			include_once plugin_basename( 'classes/class-dibs-ajax-calls.php' );
			include_once plugin_basename( 'classes/class-dibs-post-checkout.php' );
			include_once plugin_basename( 'classes/class-dibs-admin-notices.php' );
			include_once plugin_basename( 'classes/class-dibs-api-callbacks.php' );
			include_once plugin_basename( 'classes/class-dibs-confirmation.php' );
			include_once plugin_basename( 'classes/class-dibs-logger.php' );

			include_once plugin_basename( 'classes/class-dibs-subscriptions.php' );

			include_once plugin_basename( 'includes/dibs-country-converter-functions.php' );
			include_once plugin_basename( 'includes/dibs-checkout-functions.php' );

			include_once plugin_basename( 'classes/requests/class-dibs-requests.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-requests-create-dibs-order.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-requests-update-dibs-order.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-requests-update-dibs-order-reference.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-requests-activate-order.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-requests-cancel-order.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-requests-refund-order.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-requests-get-dibs-order.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-requests-charge-subscription.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-requests-get-subscription-bulk-charge-id.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-requests-get-subscription.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-requests-get-subscription-by-external-reference.php' );

			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-checkout.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-header.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-user-agent.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-items.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-order-items.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-order.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-notifications.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-order.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-payment-methods.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-refund-data.php' );

			load_plugin_textdomain( 'dibs-easy-for-woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

			$this->init_gateway();

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

			if ( 'embedded' === $this->checkout_flow ) {
				// Save DIBS data (payment id) in WC order.
				add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_dibs_order_data' ), 10, 2 );

				add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );

			}

			// Set variables for shorthand access to classes.
			$this->order_management = new DIBS_Post_Checkout();
			$this->logger           = new DIBS_Logger();

		}


		/**
		 * Add the gateway to WooCommerce
		 */
		public function init_gateway() {
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}
			include_once plugin_basename( 'classes/class-dibs-easy-gateway.php' );

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_dibs_easy' ) );
		}

		/**
		 * Load the needed JS scripts.
		 */
		public function load_scripts() {
			if ( is_checkout() && ! is_wc_endpoint_url( 'order-pay' ) ) {
				$testmode        = 'yes' === $this->dibs_settings['test_mode'];
				$script_url      = $testmode ? 'https://test.checkout.dibspayment.eu/v1/checkout.js?v=1' : 'https://checkout.dibspayment.eu/v1/checkout.js?v=1';
				$dibs_payment_id = filter_input( INPUT_GET, 'dibs-payment-id', FILTER_SANITIZE_STRING );
				$dibs_payment_id = $dibs_payment_id ? $dibs_payment_id : null;
				$paymentId       = filter_input( INPUT_GET, 'paymentId', FILTER_SANITIZE_STRING );  // phpcs:ignore
				$paymentId       = $paymentId ? $paymentId : null;  // phpcs:ignore
				$paymentFailed   = filter_input( INPUT_GET, 'paymentFailed', FILTER_SANITIZE_STRING );  // phpcs:ignore
				$paymentFailed   = $paymentFailed ? $paymentFailed : null;  // phpcs:ignore

				if ( WC()->session->get( 'dibs_payment_id' ) ) {
					$checkout_initiated = 'yes';
				} else {
					$checkout_initiated = 'no';
				}

				$standard_woo_checkout_fields = array( 'billing_first_name', 'billing_last_name', 'billing_address_1', 'billing_address_2', 'billing_postcode', 'billing_city', 'billing_phone', 'billing_email', 'billing_state', 'billing_country', 'billing_company', 'shipping_first_name', 'shipping_last_name', 'shipping_address_1', 'shipping_address_2', 'shipping_postcode', 'shipping_city', 'shipping_state', 'shipping_country', 'shipping_company', 'terms', 'account_username', 'account_password' );

				wp_enqueue_script( 'dibs-script', $script_url, array( 'jquery' ), WC_DIBS_EASY_VERSION, false );
				wp_register_script( 'checkout', plugins_url( '/assets/js/checkout.js', __FILE__ ), array( 'jquery' ), WC_DIBS_EASY_VERSION, false );
				wp_localize_script(
					'checkout',
					'wc_dibs_easy',
					array(
						'dibs_payment_id'                  => $dibs_payment_id,
						'paymentId'                        => $paymentId, // phpcs:ignore
						'paymentFailed'                    => $paymentFailed, // phpcs:ignore
						'checkout_initiated'               => $checkout_initiated,
						'standard_woo_checkout_fields'     => $standard_woo_checkout_fields,
						'dibs_process_order_text'          => __( 'Please wait while we process your order...', 'dibs-easy-for-woocommerce' ),
						'required_fields_text'             => __( 'Please fill in all required checkout fields.', 'dibs-easy-for-woocommerce' ),
						'update_checkout_url'              => WC_AJAX::get_endpoint( 'update_checkout' ),
						'customer_adress_updated_url'      => WC_AJAX::get_endpoint( 'customer_adress_updated' ),
						'get_order_data_url'               => WC_AJAX::get_endpoint( 'get_order_data' ),
						'dibs_add_customer_order_note_url' => WC_AJAX::get_endpoint( 'dibs_add_customer_order_note' ),
						'change_payment_method_url'        => WC_AJAX::get_endpoint( 'change_payment_method' ),
						'nets_checkout_nonce'              => wp_create_nonce( 'nets_checkout' ),
					)
				);
				wp_enqueue_script( 'checkout' );

				// Load stylesheet for the checkout page.
				wp_register_style(
					'dibs_style',
					plugins_url( '/assets/css/style.css', __FILE__ ),
					array(),
					WC_DIBS_EASY_VERSION
				);
				wp_enqueue_style( 'dibs_style' );
			}
		}

		/**
		 * Adds plugin action links
		 *
		 * @param array $links The links displayed in plugin page.
		 *
		 * @return array $links Plugin page links.
		 * @since 1.0.4
		 */
		public function plugin_action_links( $links ) {

			$plugin_links = array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=dibs_easy' ) . '">' . __( 'Settings', 'dibs-easy-for-woocommerce' ) . '</a>',
				'<a href="https://docs.krokedil.com/collection/197-dibs-easy">' . __( 'Docs', 'dibs-easy-for-woocommerce' ) . '</a>',
				'<a href="https://krokedil.se/support/">' . __( 'Support', 'dibs-easy-for-woocommerce' ) . '</a>',
			);
			return array_merge( $plugin_links, $links );
		}

		/**
		 * Add the gateway to WooCommerce
		 *
		 * @param  array $methods Payment methods.
		 *
		 * @return array $methods Payment methods.
		 */
		public function add_dibs_easy( $methods ) {
			$methods[] = 'DIBS_Easy_Gateway';

			return $methods;
		}

		/**
		 * Add Nets Easy related information to WooCommerce order emails.
		 *
		 * @param  object $order WooCommerce order.
		 * @param  bool   $sent_to_admin Email to admin or not.
		 * @param  bool   $plain_text Email html or text format.
		 *
		 * @return void
		 */
		public function email_extra_information( $order, $sent_to_admin, $plain_text = false ) {
			$order_id     = $order->get_id();
			$gateway_used = get_post_meta( $order_id, '_payment_method', true );
			if ( 'dibs_easy' === $gateway_used ) {
				$payment_id     = get_post_meta( $order_id, '_dibs_payment_id', true );
				$customer_card  = get_post_meta( $order_id, 'dibs_customer_card', true );
				$payment_method = get_post_meta( $order_id, 'dibs_payment_method', true );
				$order_date     = wc_format_datetime( $order->get_date_created() );
				$dibs_settings  = $this->dibs_settings;

				if ( $dibs_settings['email_text'] ) {
						echo wp_kses_post( wpautop( wptexturize( $dibs_settings['email_text'] ) ) );
				}
				if ( $order_date ) {
					echo wp_kses_post( wpautop( wptexturize( __( 'Order date: ', 'dibs-easy-for-woocommerce' ) . $order_date ) ) );
				}
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

		/**
		 * Saves DIBS data to WooCommerce order as meta field.
		 *
		 * @param string $order_id WooCommerce order id.
		 * @param array  $data  Posted data.
		 */
		public function save_dibs_order_data( $order_id, $data ) {
			$payment_id = filter_input( INPUT_POST, 'dibs_payment_id', FILTER_SANITIZE_STRING );
			Nets_Easy()->logger->log( 'Saving Nets meta data for payment id ' . $payment_id . ' in order id ' . $order_id );
			update_post_meta( $order_id, '_dibs_payment_id', $payment_id );
		}
	}

	DIBS_Easy::get_instance();
	/**
	 * Main instance DIBS_Easy.
	 *
	 * Returns the main instance of DIBS_Easy.
	 *
	 * @return DIBS_Easy
	 */
	function Nets_Easy() { // phpcs:ignore
		return DIBS_Easy::get_instance();
	}
}
