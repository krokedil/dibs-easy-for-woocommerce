<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
class DIBS_Requests_Update_DIBS_Order_Reference extends DIBS_Requests2 {

	public $payment_id;
	public $order_id;

	public function __construct( $payment_id, $order_id ) {
		$this->payment_id = $payment_id;
		$this->order_id   = $order_id;

		parent::__construct();
	}

	public function request() {
		$request_url = $this->endpoint . 'payments/' . $this->payment_id . '/referenceinformation';

		$response = wp_remote_request( $request_url, $this->get_request_args() );
		DIBS_Easy::log( 'DIBS Update Order reference response (' . $request_url . '): ' . stripslashes_deep( json_encode( $response ) ) );
		if ( is_wp_error( $response ) ) {
			$this->get_error_message( $response );
			return 'ERROR';
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			return 'SUCCESS';
		} else {
			$this->get_error_message( $response );
			return 'ERROR';
		}
	}

	public function get_request_args() {
		$request_args = array(
			'headers' => $this->request_headers(),
			'method'  => 'PUT',
			'body'    => json_encode( $this->request_body() ),
		);
		DIBS_Easy::log( 'DIBS Update Order reference args: ' . stripslashes_deep( json_encode( $request_args ) ) );

		return $request_args;
	}
	public function request_body() {
		return array(
			'reference'   => $this->order_id,
			'checkoutUrl' => wc_get_checkout_url(),
		);
	}
}
