<?php
/**
 * This Nexi Checkout Trustly payment method class.
 *
 * @package Nexi/PaymentMethods
 */

namespace Krokedil\Nexi\PaymentMethods;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trustly class
 */
class Trustly extends BaseGateway {
	/**
	 * Trustly constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->id                  = 'nets_easy_trustly';
		$this->method_title        = __( 'Nexi Checkout Trustly', 'dibs-easy-for-woocommerce' );
		$this->method_description  = __( 'Nexi Checkout Trustly payment', 'dibs-easy-for-woocommerce' );
		$this->payment_method_name = 'Trustly';

		$this->init_form_fields();
		$this->init_settings();

		$this->title   = $this->get_option( 'title', $this->method_title );
		$this->enabled = $this->get_option( 'enabled' );

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

		if ( WC()->customer && method_exists( WC()->customer, 'get_billing_country' ) ) {
			if ( ! in_array( WC()->customer->get_billing_country(), $this->available_countries, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include WC_DIBS_PATH . '/includes/nets-easy-settings-trustly.php';
	}
}
