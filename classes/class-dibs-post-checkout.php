<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Post_Checkout{
	public function __construct() {
		add_action( 'woocommerce_order_status_completed', array($this , 'dibs_order_completed'));
	}

	public function dibs_order_completed( $order_id ) {
		//Get the order information
		$order = new DIBS_Get_WC_Cart();
		$body = $order->getOrderCart($order_id);

		//Get paymentID from order meta and set endpoint
		$paymentID = get_post_meta($order_id, '_paymentID')[0];
		$gateway = new DIBS_Easy_Gateway();
		$endpoint = $gateway->endpoint . '/' . $paymentID . '/charges';
		$response = wp_remote_request( $endpoint, array(
				'method'  => 'POST',
				'headers' => array(
					"Content-type"  => "application/json",
					"Accept"        => "application/json",
					"Authorization" => $gateway->key,
				),
				'body'    => $body,
			)
		);
		$response = wp_remote_retrieve_body( $response );
		$response = json_decode($response);

		$order = wc_get_order($order_id);
		$order->add_order_note( sprintf( __( 'Payment made in DIBS with charge ID ' . $response->chargeId, 'woocommerce-dibs-easy' )) );
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		// Refund the order here
	}
}
$dibs_post_checkout = new DIBS_Post_Checkout();