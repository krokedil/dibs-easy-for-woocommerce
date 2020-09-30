<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * DIBS_OSF class.
 */
class DIBS_OSF {
	public $endpoint;

	public $key;

	public $testmode;

	public static $log = false;

	/**
	 * DIBS_OSF constructor.
	 */
	function __construct() {
		// Set the endpoint and key from settings
		$dibs_settings  = get_option( 'woocommerce_dibs_easy_settings' );
		$this->testmode = 'yes' === $dibs_settings['test_mode'];
		$this->key      = $this->testmode ? $dibs_settings['dibs_test_key'] : $dibs_settings['dibs_live_key'];
		$this->endpoint = $this->testmode ? 'https://test.api.dibspayment.eu/v1/' : 'https://api.dibspayment.eu/v1/';

		add_action( 'init', array( $this, 'maybe_create_backup_order_finalization' ) );
	}
	/**
	 * If order is not submitted correctly via javascript in checkout the parameter ecster-osf=true is added to the url when checkout page is reloaded.
	 * We listen to this in the maybe_create_backup_order_finalization() function.
	 */
	public function maybe_create_backup_order_finalization() {
		if ( isset( $_GET['dibs-osf'] ) && true == $_GET['dibs-osf'] && isset( $_GET['order-id'] ) ) {
			$order_id = $_GET['order-id'];
			$order    = wc_get_order( $order_id );

			if ( ! $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
				$payment_id = get_post_meta( $order_id, '_dibs_payment_id', true );

				$request = new DIBS_Requests_Get_DIBS_Order( $payment_id, $order_id );
				$request = $request->request();
				if ( isset( $request->payment->summary->reservedAmount ) || isset( $request->payment->summary->chargedAmount ) || isset( $request->payment->subscription->id ) ) {
					update_post_meta( $order_id, 'dibs_payment_type', $request->payment->paymentDetails->paymentType );
					update_post_meta( $order_id, '_dibs_date_paid', date( 'Y-m-d H:i:s' ) );

					if ( 'CARD' == $request->payment->paymentDetails->paymentType ) {
						update_post_meta( $order_id, 'dibs_customer_card', $request->payment->paymentDetails->cardDetails->maskedPan );
					}

					$order->add_order_note( sprintf( __( 'New payment created in Nets Easy with Payment ID %1$s. Payment type - %2$s.', 'dibs-easy-for-woocommerce' ), $payment_id, $request->payment->paymentDetails->paymentType ) );
					$order->payment_complete( $payment_id );
					WC()->cart->empty_cart();
				}
				WC()->session->__unset( 'order_awaiting_payment' );
				$order->update_status( 'on-hold', __( 'Order created on checkout error fallback. Please verify the order to make sure its correct.', 'dibs-easy-for-woocommerce' ) );
				$order->save();
				update_post_meta( $order_id, '_dibs_osf', true );
			}
			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		}
	}
}
$dibs_osf = new DIBS_OSF();
