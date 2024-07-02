<?php // phpcs:ignore
/**
 * Nexi Checkout
 *
 * @package WC_Dibs_Easy
 *
 * @wordpress-plugin
 * Plugin Name:             Nexi Checkout
 * Plugin URI:              https://krokedil.se/produkt/nets-easy/
 * Description:             Extends WooCommerce. Provides a <a href="http://developer.nexigroup.com/nexi-checkout/en-EU/docs/checkout-for-woocommerce/" target="_blank">Nexi Checkout</a> payment solution for WooCommerce.
 * Version:                 2.9.1
 * Author:                  Krokedil
 * Author URI:              https://krokedil.se/
 * Developer:               Krokedil
 * Developer URI:           https://krokedil.se/
 * Text Domain:             dibs-easy-for-woocommerce
 * Domain Path:             /languages
 * WC requires at least:    5.6.0
 * WC tested up to:         9.0.0
 * Copyright:               © 2017-2024 Krokedil AB.
 * License:                 GNU General Public License v3.0
 * License URI:             http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Required minimums and constants
 */
define( 'WC_DIBS_EASY_VERSION', '2.9.1' );
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
		 * @var $array
		 */
		public $dibs_settings;

		/**
		 * Api class.
		 *
		 * @var Nets_Easy_API
		 */
		public $api;

		/**
		 * The checkout type
		 *
		 * @var string
		 */
		public $checkout_flow;

		/**
		 * The order management
		 *
		 * @var $order_management
		 */
		public $order_management;

		/**
		 * Enable Payment Method Card
		 *
		 * @var $enable_payment_method_card
		 */
		public $enable_payment_method_card;

		/**
		 * Enable Payment Method Sofort payment.
		 *
		 * @var $enable_payment_method_sofort
		 */
		public $enable_payment_method_sofort;

		/**
		 * Enable Payment Method Trustly payment.
		 *
		 * @var $enable_payment_method_trustly
		 */
		public $enable_payment_method_trustly;

		/**
		 * Enable Payment Method Swish payment.
		 *
		 * @var $enable_payment_method_swish
		 */
		public $enable_payment_method_swish;

		/**
		 * Enable Payment Method Ratepay payment.
		 *
		 * @var $enable_payment_method_ratepay_sepa
		 */
		public $enable_payment_method_ratepay_sepa;

		/**
		 * DIBS_Easy constructor.
		 */
		public function __construct() {
			$this->dibs_settings                      = get_option( 'woocommerce_dibs_easy_settings' );
			$this->checkout_flow                      = $this->dibs_settings['checkout_flow'] ?? 'embedded';
			$this->enable_payment_method_card         = $this->dibs_settings['enable_payment_method_card'] ?? 'no';
			$this->enable_payment_method_sofort       = $this->dibs_settings['enable_payment_method_sofort'] ?? 'no';
			$this->enable_payment_method_trustly      = $this->dibs_settings['enable_payment_method_trustly'] ?? 'no';
			$this->enable_payment_method_swish        = $this->dibs_settings['enable_payment_method_swish'] ?? 'no';
			$this->enable_payment_method_ratepay_sepa = $this->dibs_settings['enable_payment_method_ratepay_sepa'] ?? 'no';

			add_action( 'plugins_loaded', array( $this, 'init' ) );
			add_action( 'woocommerce_blocks_loaded', array( $this, 'register_block_method' ) );
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
				include_once plugin_basename( 'classes/class-nets-easy-templates.php' );
			}

			include_once plugin_basename( 'classes/class-nets-easy-ajax.php' );
			include_once plugin_basename( 'classes/class-nets-easy-order-management.php' );
			include_once plugin_basename( 'classes/class-nets-easy-admin-notices.php' );
			include_once plugin_basename( 'classes/class-nets-easy-api-callbacks.php' );
			include_once plugin_basename( 'classes/class-nets-easy-confirmation.php' );
			include_once plugin_basename( 'classes/class-nets-easy-logger.php' );
			include_once plugin_basename( 'classes/class-nets-easy-email.php' );

			include_once plugin_basename( 'classes/class-nets-easy-subscriptions.php' );

			include_once plugin_basename( 'includes/nets-easy-country-converter.php' );
			include_once plugin_basename( 'includes/nets-easy-functions.php' );

			include_once plugin_basename( 'classes/requests/class-nets-easy-request.php' );
			include_once plugin_basename( 'classes/requests/class-nets-easy-request-post.php' );
			include_once plugin_basename( 'classes/requests/class-nets-easy-request-put.php' );
			include_once plugin_basename( 'classes/requests/class-nets-easy-request-get.php' );
			include_once plugin_basename( 'classes/requests/post/class-nets-easy-request-create-order.php' );
			include_once plugin_basename( 'classes/requests/put/class-nets-easy-request-update-order.php' );
			include_once plugin_basename( 'classes/requests/put/class-nets-easy-request-update-order-reference.php' );
			include_once plugin_basename( 'classes/requests/post/class-nets-easy-request-activate-order.php' );
			include_once plugin_basename( 'classes/requests/post/class-nets-easy-request-cancel-order.php' );
			include_once plugin_basename( 'classes/requests/post/class-nets-easy-request-refund-order.php' );
			include_once plugin_basename( 'classes/requests/get/class-nets-easy-request-get-order.php' );
			include_once plugin_basename( 'classes/requests/post/class-nets-easy-request-charge-subscription.php' );
			include_once plugin_basename( 'classes/requests/post/class-nets-easy-request-charge-unscheduled-subscription.php' );
			include_once plugin_basename( 'classes/requests/get/class-nets-easy-request-get-subscription-bulk-charge-id.php' );
			include_once plugin_basename( 'classes/requests/get/class-nets-easy-request-get-subscription.php' );
			include_once plugin_basename( 'classes/requests/get/class-nets-easy-request-get-subscription-by-external-reference.php' );
			include_once plugin_basename( 'classes/requests/get/class-nets-easy-request-get-unscheduled-subscription-by-external-reference.php' );

			include_once plugin_basename( 'classes/requests/helpers/class-nets-easy-checkout-helper.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-nets-easy-cart-helper.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-nets-easy-order-items-helper.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-nets-easy-order-helper.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-nets-easy-notification-helper.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-nets-easy-order-helper.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-nets-easy-payment-method-helper.php' );
			include_once plugin_basename( 'classes/requests/helpers/class-nets-easy-refund-helper.php' );
			include_once plugin_basename( 'classes/class-nets-easy-assets.php' );
			include_once plugin_basename( 'classes/class-nets-easy-api.php' );
			include_once plugin_basename( 'classes/class-nets-easy-checkout.php' );

			load_plugin_textdomain( 'dibs-easy-for-woocommerce', false, plugin_basename( __DIR__ ) . '/languages' );

			$this->init_gateway();

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

			// Set variables for shorthand access to classes.
			$this->order_management = new Nets_Easy_Order_Management();

			$this->api = new Nets_Easy_API();
		}


		/**
		 * Add the gateway to WooCommerce
		 */
		public function init_gateway() {
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}
			include_once plugin_basename( 'classes/class-nets-easy-gateway.php' );
			include_once plugin_basename( 'classes/payment-methods/class-nets-easy-gateway-card.php' );
			include_once plugin_basename( 'classes/payment-methods/class-nets-easy-gateway-sofort.php' );
			include_once plugin_basename( 'classes/payment-methods/class-nets-easy-gateway-trustly.php' );
			include_once plugin_basename( 'classes/payment-methods/class-nets-easy-gateway-ratepay-sepa.php' );

			include_once plugin_basename( 'classes/payment-methods/class-nets-easy-gateway-swish.php' );

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
			$methods[] = Nets_Easy_Gateway::class;

			// Maybe enable Card payment.
			if ( 'yes' === $this->enable_payment_method_card ) {
				$methods[] = Nets_Easy_Gateway_Card::class;
			}

			// Maybe enable Sofort payment.
			if ( 'yes' === $this->enable_payment_method_sofort ) {
				$methods[] = Nets_Easy_Gateway_Sofort::class;
			}

			// Maybe enable Trustly payment.
			if ( 'yes' === $this->enable_payment_method_trustly ) {
				$methods[] = Nets_Easy_Gateway_Trustly::class;
			}

			// Maybe enable Swish payment.
			if ( 'yes' === $this->enable_payment_method_swish ) {
				$methods[] = Nets_Easy_Gateway_Swish::class;
			}

			// Maybe enable Ratepay payment.
			if ( 'yes' === $this->enable_payment_method_ratepay_sepa ) {
				$methods[] = Nets_Easy_Gateway_Ratepay_Sepa::class;
			}

			return $methods;
		}

		/**
		 * Register the Checkout blocks method.
		 *
		 * @return void
		 */
		public function register_block_method() {
			if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
				require_once __DIR__ . '/blocks/src/checkout/class-nets-easy-checkout-block.php';

				$settings                    = get_option( 'woocommerce_dibs_easy_settings', array() );
				$main_payment_method_enabled = $settings['enabled'] ?? 'no';

				$payment_methods = array(
					'dibs_easy'              => 'yes' === $main_payment_method_enabled,
					'nets_easy_card'         => 'yes' === $this->enable_payment_method_card,
					'nets_easy_sofort'       => 'yes' === $this->enable_payment_method_sofort,
					'nets_easy_trustly'      => 'yes' === $this->enable_payment_method_trustly,
					'nets_easy_swish'        => 'yes' === $this->enable_payment_method_swish,
					'nets_easy_ratepay_sepa' => 'yes' === $this->enable_payment_method_ratepay_sepa,
				);

				add_action(
					'woocommerce_blocks_payment_method_type_registration',
					function ( $payment_method_registry ) use ( $payment_methods ) {
						$payment_method_registry->register( new Nets_Easy_Checkout_Block( $payment_methods ) );
					}
				);
			}
		}
	}

	add_action(
		'before_woocommerce_init',
		function () {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}
	);

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
