<?php
/**
 * This Nexi Checkout Ratepay SEPA payment method class.
 *
 * @package Nexi/PaymentMethods
 */

namespace Krokedil\Nexi\PaymentMethods;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ratepay_Sepa class
 */
class Ratepay_Sepa extends BaseGateway {
	/**
	 * Ratepay_Sepa constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->id                   = 'nets_easy_ratepay_sepa';
		$this->method_title         = __( 'Nexi Checkout Ratepay SEPA', 'dibs-easy-for-woocommerce' );
		$this->method_description   = __( 'Nexi Checkout Ratepay SEPA payment', 'dibs-easy-for-woocommerce' );
		$this->payment_method_name  = 'RatePaySepa';
		$this->available_currencies = array( 'EUR' );

		$this->supports = array(
			'products',
			'refunds',
		);

		add_action( "woocommerce_update_options_payment_gateways_$this->id", array( $this, 'process_admin_options' ) );
	}

	/**
	 * Checks if method should be available.
	 *
	 * @return bool
	 */
	public function check_availability() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return true;
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
		$this->form_fields = include WC_DIBS_PATH . '/includes/nets-easy-settings-ratepay-sepa.php';
	}
}
