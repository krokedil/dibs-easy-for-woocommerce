<?php
/**
 * This Nexi Checkout Card payment method class.
 *
 * @package Nexi/PaymentMethods
 */

namespace Krokedil\Nexi\PaymentMethods;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Card class
 */
class Card extends BaseGateway {

	/**
	 * Card constructor.
	 */
	public function __construct() {
		$this->id                  = 'nets_easy_card';
		$this->method_title        = __( 'Nexi Checkout Card', 'dibs-easy-for-woocommerce' );
		$this->method_description  = __( 'Nexi Checkout Card payment', 'dibs-easy-for-woocommerce' );
		$this->payment_method_name = 'Card';

		$this->init_form_fields();
		$this->init_settings();

		$this->supports = array(
			'products',
			'refunds',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'subscription_payment_method_change',
			'multiple_subscriptions',
		);

		add_action( "woocommerce_update_options_payment_gateways_$this->id", array( $this, 'process_admin_options' ) );
		parent::__construct();
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include WC_DIBS_PATH . '/includes/nets-easy-settings-card.php';
	}
}
