<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Easy_Gateway extends WC_Payment_Gateway {

	public $endpoint;

	public $key;

	public $private_key;

	public $testmode;

	public $language;

	public $checkout_url;

	public $thank_you_url;

	public function __construct() {
		$this->id = 'dibs_easy';

		$this->method_title = __( 'DIBS Easy', 'woocommerce-gateway-klarna' );

		$this->method_description = 'DIBS Easy Payment for checkout';

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings
		$this->init_settings();
		// Get the settings values
		$this->title        = $this->get_option('title');

		$this->enabled      = $this->get_option( 'enabled' );

		$this->testmode     = 'yes' === $this->get_option( 'test_mode' );

		$this->endpoint     = $this->testmode ? "https://test.api.dibspayment.eu/v1/payments" : "https://checkout.dibspayment.eu/v1/checkout.js?v=1";

		$this->key          = $this->testmode ? $this->get_option( 'dibs_test_key' ) : $this->get_option( 'dibs_live_key' );

		$this->private_key  = $this->get_option( 'dibs_private_key' );

		$this->language     = $this->get_option( 'dibs_language' );

		$this->checkout_url = $this->get_option( 'dibs_checkout_url' );

		// Add actions for when order is processed
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		//add_action( 'woocommerce_order_status_completed', array($this , 'dibs_order_completed'));
	}
	public function init_form_fields() {
		$this->form_fields = include( DIR_NAME . '/includes/dibs-settings.php' );
	}
	public function process_payment( $order_id, $retry = false ) {
		$order = wc_get_order( $order_id );

		WC()->cart->empty_cart();

		$order->payment_complete();

		WC()->session->__unset( 'order_awaiting_payment' );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}
	public function listener(){
		//
	}


}// End of class DIBS_Easy_Gateway