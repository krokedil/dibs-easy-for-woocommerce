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
 * Nets_Easy_Assets class.
 */
class Nets_Easy_Assets {

	/**
	 * The plugin settings.
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Enabled status
	 *
	 * @var string
	 */
	public $enabled;

	/**
	 * Test mode status
	 *
	 * @var string
	 */
	public $test_mode;

	/**
	 * The checkout flow
	 *
	 * @var string
	 */
	public $checkout_flow;

	/**
	 * Dibs_Easy_Assets constructor.
	 */
	public function __construct() {
		$this->settings      = get_option( 'woocommerce_dibs_easy_settings', array() );
		$this->enabled       = $this->settings['enabled'] ?? 'no';
		$this->test_mode     = $this->settings['test_mode'] ?? 'no';
		$this->checkout_flow = $this->settings['checkout_flow'] ?? '';

		if ( 'embedded' === $this->checkout_flow ) {

			/* For block-based themes, the template process is processed BEFORE the `wp_enqueue_script`. We need to enqueue the scripts in 'dibs_load_js' before the localized script is enqueued which depends on the former. The `init` hook cannot be used since `is_checkout` always returns false. With `template_redirect`, `is_checkout` returns the correct value, and is processed before the localization script is enqueued. */
			add_action( 'template_redirect', array( $this, 'dibs_load_js' ), 10 );
			add_action( 'wc_dibs_before_checkout_form', array( $this, 'localize_and_enqueue_checkout_script' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'dibs_load_css' ), 10 );
		}

		if ( 'overlay' === $this->checkout_flow ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'load_overlay_js' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'dibs_overlay_css' ) );
		}

	}

	/**
	 *
	 * Checks whether a SCRIPT_DEBUG constant exists.
	 * If there is, the plugin will use non minified files.
	 *
	 * @return string
	 */
	protected function nets_easy_is_script_debug_enabled() {
		return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	}

	/**
	 *  Returns script URL based on plugin mode.
	 *
	 * @return string
	 */
	protected function get_script_url() {
		if ( 'yes' === $this->test_mode ) {
			return 'https://test.checkout.dibspayment.eu/v1/checkout.js?v=1';
		}
		return 'https://checkout.dibspayment.eu/v1/checkout.js?v=1';
	}

	/**
	 * Loads scripts for the plugin.
	 */
	public function dibs_load_js() {

		if ( 'yes' !== $this->enabled ) {
			return;
		}

		/* On the 'order-pay' page we redirect the customer to a hosted payment page, and therefore don't need need to enqueue any of the following assets. */
		if ( ! is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) {
			return;
		}

		// There is no 'thank-you' snippet to show. Use the standard WC template.
		if ( is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		// Nets regular checkout script.
		$script_url = $this->get_script_url();
		wp_enqueue_script( 'dibs-script', $script_url, array( 'jquery' ), WC_DIBS_EASY_VERSION, false );

		// Plugin checkout script. Registered here, but localized and enqueued on the wc_dibs_before_checkout_form hook.
		$script_version = $this->nets_easy_is_script_debug_enabled();
		$src            = WC_DIBS__URL . '/assets/js/nets-easy-for-woocommerce' . $script_version . '.js';
		wp_register_script(
			'nets-easy-checkout',
			$src,
			array(
				'jquery',
				'dibs-script',
			),
			WC_DIBS_EASY_VERSION,
			false
		);

		// Checkout utility (change to Nexi Checkout payment method in checkout).
		wp_register_script(
			'nets_easy_utility',
			WC_DIBS__URL . '/assets/js/nets-easy-utility.js',
			array( 'jquery' ),
			WC_DIBS_EASY_VERSION,
			true
		);

		$params = array(
			'change_payment_method_url' => WC_AJAX::get_endpoint( 'change_payment_method' ),
			'nets_checkout_nonce'       => wp_create_nonce( 'nets_checkout' ),
		);

		wp_localize_script(
			'nets_easy_utility',
			'nets_easy_utility_params',
			$params
		);
		wp_enqueue_script( 'nets_easy_utility' );
	}

	/**
	 * Loads scripts for the plugin - overlay checkout mode.
	 */
	public function load_overlay_js() {

		if ( 'yes' !== $this->enabled ) {
			return;
		}

		/* On the 'order-pay' page we redirect the customer to a hosted payment page, and therefore don't need need to enqueue any of the following assets. */
		if ( ! is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) {
			return;
		}

		// There is no 'thank-you' snippet to show. Use the standard WC template.
		if ( is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		// Checkout overlay.
		wp_register_script(
			'nets_easy_overlay',
			WC_DIBS__URL . '/assets/js/nets-easy-overlay.js',
			array( 'jquery' ),
			WC_DIBS_EASY_VERSION,
			true
		);

		$params = array();

		wp_localize_script(
			'nets_easy_overlay',
			'nets_easy_overlay_params',
			$params
		);
		wp_enqueue_script( 'nets_easy_overlay' );
	}

	/**
	 * Loads the needed scripts for Nexi Checkout.
	 */
	public function localize_and_enqueue_checkout_script() {

		if ( WC()->session->get( 'dibs_payment_id' ) ) {
			$checkout_initiated = 'yes';
		} else {
			$checkout_initiated = 'no';
			$easy_confirm       = filter_input( INPUT_GET, 'easy_confirm', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( empty( $easy_confirm ) ) {
				dibs_easy_maybe_create_order();
				$checkout_initiated = 'yes';
			}
		}

		$standard_woo_checkout_fields = apply_filters(
			'nets_easy_ignored_checkout_fields',
			array(
				'billing_first_name',
				'billing_last_name',
				'billing_address_1',
				'billing_address_2',
				'billing_postcode',
				'billing_city',
				'billing_phone',
				'billing_email',
				'billing_state',
				'billing_country',
				'billing_company',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_postcode',
				'shipping_city',
				'shipping_state',
				'shipping_country',
				'shipping_company',
				'terms',
				'account_username',
				'account_password',
			)
		);

		// todo enable min version.
		// phpcs:ignore $src                          = WC_DIBS__URL . '/assets/js/checkout' . $script_version . '.js';

		$private_key = 'yes' === $this->test_mode ? $this->settings['dibs_test_checkout_key'] : $this->settings['dibs_checkout_key'];
		wp_localize_script(
			'nets-easy-checkout',
			'wcDibsEasy',
			array(
				'dibs_payment_id'                  => WC()->session->get( 'dibs_payment_id' ),
				'checkoutInitiated'                => $checkout_initiated,
				'standard_woo_checkout_fields'     => $standard_woo_checkout_fields,
				'dibs_process_order_text'          => __( 'Please wait while we process your order...', 'dibs-easy-for-woocommerce' ),
				'required_fields_text'             => __( 'Please fill in all required checkout fields.', 'dibs-easy-for-woocommerce' ),
				'customer_address_updated_url'     => WC_AJAX::get_endpoint( 'customer_address_updated' ),
				'get_order_data_url'               => WC_AJAX::get_endpoint( 'get_order_data' ),
				'submitOrder'                      => WC_AJAX::get_endpoint( 'checkout' ),
				'dibs_add_customer_order_note_url' => WC_AJAX::get_endpoint( 'dibs_add_customer_order_note' ),
				'change_payment_method_url'        => WC_AJAX::get_endpoint( 'change_payment_method' ),
				'log_to_file_url'                  => WC_AJAX::get_endpoint( 'dibs_easy_wc_log_js' ),
				'log_to_file_nonce'                => wp_create_nonce( 'dibs_easy_wc_log_js' ),
				'nets_checkout_nonce'              => wp_create_nonce( 'nets_checkout' ),
				'privateKey'                       => $private_key,
				'locale'                           => wc_dibs_get_locale(),
				'isMobile'                         => wp_is_mobile(),
				'shippingSelector'                 => apply_filters( 'nets_easy_shipping_selector', '.woocommerce-checkout-review-order-table' ),
			)
		);
		wp_enqueue_script( 'nets-easy-checkout' );
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
		$style_version = $this->nets_easy_is_script_debug_enabled();
		// Load stylesheet for the checkout page.
		wp_register_style(
			'dibs-style',
			WC_DIBS__URL . '/assets/css/style' . $style_version . '.css',
			array(),
			WC_DIBS_EASY_VERSION
		);

		wp_enqueue_style( 'dibs-style' );
	}

	/**
	 * Loads style for the plugin.
	 */
	public function dibs_overlay_css() {
		if ( ! is_checkout() ) {
			return;
		}
		if ( is_order_received_page() ) {
			return;
		}
		$style_version = $this->nets_easy_is_script_debug_enabled();
		// Load stylesheet for the checkout page.
		wp_register_style(
			'nets-easy-overlay-style',
			WC_DIBS__URL . '/assets/css/nets-easy-overlay.css',
			array(),
			WC_DIBS_EASY_VERSION
		);

		wp_enqueue_style( 'nets-easy-overlay-style' );
	}
}
new Nets_Easy_Assets();
