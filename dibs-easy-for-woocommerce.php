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
 * Version:                 1.25.0
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
define( 'WC_DIBS_EASY_VERSION', '1.25.0' );
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
		 * Api class.
		 *
		 * @var Dibs_Easy_API
		 */
		public $api;

		/**
		 * The checkout type
		 *
		 * @var string
		 */
		public $checkout_flow;

		/**
		 * DIBS_Easy constructor.
		 */
		public function __construct() {
			$this->dibs_settings = get_option( 'woocommerce_dibs_easy_settings' );
			$this->checkout_flow = $this->dibs_settings['checkout_flow'] ?? 'embedded';
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
		public function __wakeup() {
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
			include_once plugin_basename( 'classes/class-dibs-easy-email.php' );

			include_once plugin_basename( 'classes/class-dibs-subscriptions.php' );

			include_once plugin_basename( 'includes/dibs-country-converter-functions.php' );
			include_once plugin_basename( 'includes/dibs-checkout-functions.php' );

			include_once plugin_basename( 'classes/requests/class-dibs-requests.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-request-post.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-request-put.php' );
			include_once plugin_basename( 'classes/requests/class-dibs-request-get.php' );
			include_once plugin_basename( 'classes/requests/post/class-dibs-requests-create-dibs-order.php' );
			include_once plugin_basename( 'classes/requests/put/class-dibs-requests-update-dibs-order.php' );
			include_once plugin_basename( 'classes/requests/put/class-dibs-requests-update-dibs-order-reference.php' );
			include_once plugin_basename( 'classes/requests/post/class-dibs-requests-activate-order.php' );
			include_once plugin_basename( 'classes/requests/post/class-dibs-requests-cancel-order.php' );
			include_once plugin_basename( 'classes/requests/post/class-dibs-requests-refund-order.php' );
			include_once plugin_basename( 'classes/requests/get/class-dibs-requests-get-dibs-order.php' );
			include_once plugin_basename( 'classes/requests/post/class-dibs-requests-charge-subscription.php' );
			include_once plugin_basename( 'classes/requests/get/class-dibs-requests-get-subscription-bulk-charge-id.php' );
			include_once plugin_basename( 'classes/requests/get/class-dibs-requests-get-subscription.php' );
			include_once plugin_basename( 'classes/requests/get/class-dibs-requests-get-subscription-by-external-reference.php' );

			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-checkout.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-items.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-order-items.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-order.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-notifications.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-order.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-payment-methods.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-dibs-requests-get-refund-data.php' );
			include_once plugin_basename( 'classes/class-dibs-assets.php' );
			include_once plugin_basename( 'classes/class-dibs-api.php' );

			load_plugin_textdomain( 'dibs-easy-for-woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

			$this->init_gateway();

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

			// Set variables for shorthand access to classes.
			$this->order_management = new DIBS_Post_Checkout();

			$this->api = new Dibs_Easy_API();

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
