<?php
/**
 * Main assets file.
 *
 * @package Dibs_For_WooCommerce/Classes/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dibs_Easy_Assets class.
 */
class Dibs_Easy_Assets {

	/**
	 * The plugin settings.
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Dibs_Easy_Assets constructor.
	 */
	public function __construct() {
		$this->settings = get_option( 'woocommerce_dibs_easy_settings', array() );
		if ( ! ( empty( $this->settings ) ) && 'embedded' === $this->settings['checkout_flow'] ) {
			// TODO move : woocommerce_checkout_update_order_meta action is just temporarily here.
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_dibs_order_data' ), 10, 2 );
			add_action( 'wp_enqueue_scripts', array( $this, 'dibs_load_js' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'dibs_load_css' ) );

		}

	}

	/**
	 *
	 * Checks whether a SCRIPT_DEBUG constant exists.
	 * If there is, the plugin will use minified files.
	 *
	 * @return string
	 */
	protected function qoc_is_script_debug_enabled() {
		// TODO fix this.
		// return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		return '';
	}

	/**
	 *  Returns script URL based on plugin mode.
	 *
	 * @return string
	 */
	protected function get_script_url() {
		if ( 'yes' === $this->settings['test_mode'] ) {
			return 'https://test.checkout.dibspayment.eu/v1/checkout.js?v=1';
		}
		return 'https://checkout.dibspayment.eu/v1/checkout.js?v=1';
	}

	/**
	 * Loads scripts for the plugin.
	 */
	public function dibs_load_js() {
		$settings = get_option( 'woocommerce_dibs_easy_settings' );
		if ( 'yes' !== $settings['enabled'] ) {
			return;
		}
		if ( is_checkout() && ! is_wc_endpoint_url( 'order-pay' ) ) {
			$script_url      = $this->get_script_url();
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
			$script_version               = $this->qoc_is_script_debug_enabled();
			$src                          = WC_DIBS__URL . '/assets/js/checkout' . $script_version . '.js';
			wp_enqueue_script( 'dibs-script', $script_url, array( 'jquery' ), WC_DIBS_EASY_VERSION, false );
			wp_register_script( 'checkout', $src, array( 'jquery', 'dibs-script' ), WC_DIBS_EASY_VERSION, false );
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
		}
	}


	/**
	 * Loads style for the plugin.
	 */
	public function dibs_load_css() {
		if ( ! is_checkout() ) {
			return;
		}
		if ( is_order_received_page() ) {
			return;
		}
		$style_version = $this->qoc_is_script_debug_enabled();
		// Load stylesheet for the checkout page.
		wp_register_style(
			'qliro-one-style',
			WC_DIBS__URL . '/assets/css/style' . $style_version . '.css',
			array(),
			WC_DIBS_EASY_VERSION
		);

		wp_enqueue_style( 'dibs_style' );
	}
}
new Dibs_Easy_Assets();
