<?php
/**
 * DIBS Easy for WooCommerce
 *
 * @package WC_Dibs_Easy
 *
 * @wordpress-plugin
 * Plugin Name:     DIBS Easy for WooCommerce
 * Plugin URI:      http://krokedil.com/
 * Description:     Extends WooCommerce. Provides a <a href="http://www.dibspayment.com/" target="_blank">DIBS Easy</a> checkout for WooCommerce.
 * Version:         1.0.0
 * Author:          WooCommerce
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

		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init' ) );
			define( 'DIR_NAME' , dirname( __FILE__ ) );
		}
		// Include the classes and enqueue the scripts.
		public function init() {
			include_once( plugin_basename( 'classes/class-dibs-get-wc-cart.php' ) );
			include_once( plugin_basename( 'classes/class-dibs-ajax-calls.php' ) );
			include_once( plugin_basename( 'classes/class-dibs-post-checkout.php' ) );
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
				wp_enqueue_script( 'dibs-script', 'https://test.checkout.dibspayment.eu/v1/checkout.js?v=1', array( 'jquery' ) );
				wp_register_script( 'checkout', plugins_url( '/assets/js/checkout.js', __FILE__ ), array( 'jquery' ) );
				wp_localize_script( 'checkout', 'wc_dibs_easy', array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
				) );
				wp_enqueue_script( 'checkout' );
			}
		}

		//Add DIBS Easy gateway to WooCommerce Admin interface
		function add_dibs_easy( $methods ) {
			$methods[] = 'DIBS_Easy_Gateway';

			return $methods;
		}
	}
	$dibs_easy = new DIBS_Easy();
}