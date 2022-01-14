<?php
/**
 * Refund order request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Post Nets refund order request class
 */
class DIBS_Request_Refund_Order extends DIBS_Requests2 {

	/**
	 * $order_id. WooCommerce order ID.
	 *
	 * @var string
	 */
	public $order_id;

	/**
	 * $charge_id. Nets Easy charge ID for the order.
	 *
	 * @var string
	 */
	public $charge_id;

	/**
	 * Class constructor.
	 *
	 * @param string $order_id WC order id.
	 */
	public function __construct( $order_id ) {
		parent::__construct();

		$this->order_id = $order_id;
	}

	/**
	 * Makes the request.
	 *
	 * @return mixed
	 */
	public function request() {

		$charge_id = get_post_meta( $this->order_id, '_dibs_charge_id', true );

		if ( empty( $charge_id ) ) {
			$order = wc_get_order( $this->order_id );
			$order->add_order_note( __( 'Nets Easy order could not be refunded. Missing Charge id.', 'dibs-easy-for-woocommerce' ) );
			return;
		}

		$request_url  = $this->endpoint . 'charges/' . $charge_id . '/refunds';
		$request_args = $this->get_request_args();
		$response     = wp_remote_request( $request_url, $request_args );
		$code         = wp_remote_retrieve_response_code( $response );

		// Log the request.
		$log = Nets_Easy()->logger->format_log( $charge_id, 'POST', 'Nets refund order', $request_args, $request_url, json_decode( wp_remote_retrieve_body( $response ), true ), $code );
		Nets_Easy()->logger->log( $log );

		if ( is_wp_error( $response ) ) {
			$this->get_error_message( $response );
			return wp_json_encode( $response );
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			return wp_remote_retrieve_body( $response );
		} else {
			$this->get_error_message( $response );
			return wp_json_encode( $response );
		}
	}

	/**
	 * Gets the request args for the API call.
	 *
	 * @return array
	 */
	public function get_request_args() {
		$request_args = array(
			'headers'    => $this->request_headers( $this->order_id ),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'POST',
			'body'       => wp_json_encode( $this->request_body() ),
			'timeout'    => apply_filters( 'nets_easy_set_timeout', 10 ),
		);
		return apply_filters( 'dibs_easy_refund_order_args', $request_args );
	}

	/**
	 * Gets the request request body.
	 *
	 * @return array
	 */
	public function request_body() {
		$order = wc_get_order( $this->order_id );
		return array(
			'amount'     => DIBS_Requests_Get_Refund_Data::get_total_refund_amount( $this->order_id ),
			'orderItems' => DIBS_Requests_Get_Refund_Data::get_refund_data( $this->order_id ),
		);
	}
}
