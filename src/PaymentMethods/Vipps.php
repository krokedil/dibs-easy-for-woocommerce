<?php
/**
 * This Nexi Checkout Vipps payment method class.
 *
 * @see https://developer.nexigroup.com/nexi-checkout/en-EU/docs/vipps/
 *
 * @package Nexi/PaymentMethods
 */

namespace Krokedil\Nexi\PaymentMethods;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vipps class
 */
class Vipps extends BaseGateway {

	/**
	 * MobilePay constructor.
	 */
	public function __construct() {
		$this->id                   = 'nets_easy_vipps';
		$this->method_title         = __( 'Nexi Checkout Vipps', 'dibs-easy-for-woocommerce' );
		$this->method_description   = __( 'Nexi Checkout Vipps payment', 'dibs-easy-for-woocommerce' );
		$this->payment_method_name  = 'Vipps';
		$this->payment_gateway_icon = WC_DIBS__URL . '/assets/images/vipps.png';
		$this->available_countries  = array( 'NO' );
		$this->available_currencies = array( 'NOK' );

		$this->init_form_fields();
		$this->init_settings();

		$this->supports = array(
			'products',
			'refunds',
		);

		add_action( "woocommerce_update_options_payment_gateways_$this->id", array( $this, 'process_admin_options' ) );
		parent::__construct();
	}

	/**
	 * Checks if method should be available.
	 *
	 * @return bool
	 */
	public function check_availability() {
		$checkout_flow = $this->shared_settings['checkout_flow'] ?? null;
		if ( 'yes' !== $this->enabled || ! in_array( $checkout_flow, $this->supported_checkout_flows(), true ) ) {
			return false;
		}

		// Customer country check.
		if ( WC()->customer && method_exists( WC()->customer, 'get_billing_country' ) ) {
			if ( ! in_array( WC()->customer->get_billing_country(), $this->available_countries, true ) ) {
				return false;
			}
		}

		// Currency check.
		if ( ! in_array( get_woocommerce_currency(), $this->available_currencies, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include WC_DIBS_PATH . '/includes/nets-easy-settings-vipps.php';
	}
}
