<?php
/**
 * Update order reference request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Update order reference request class
 */
class DIBS_Requests_Update_DIBS_Order_Reference extends DIBS_Requests2 {

	/**
	 * $payment_id. Nets Payment ID.
	 *
	 * @var string
	 */
	public $payment_id;

	/**
	 * $order_id. WooCommerce order ID.
	 *
	 * @var string
	 */
	public $order_id;

	/**
	 * Class constructor.
	 *
	 * @param string $payment_id Nets payment id.
	 * @param string $order_id WC order id.
	 */
	public function __construct( $payment_id, $order_id ) {
		$this->payment_id = $payment_id;
		$this->order_id   = $order_id;

		$this->order_number = $this->get_order_number( $order_id );

		parent::__construct();
	}

	/**
	 * Makes the request.
	 *
	 * @return mixed
	 */
	public function request() {
		$request_url  = $this->endpoint . 'payments/' . $this->payment_id . '/referenceinformation';
		$request_args = $this->get_request_args();
		$response     = wp_remote_request( $request_url, $request_args );
		$code         = wp_remote_retrieve_response_code( $response );

		// Log the request.
		$log = Nets_Easy()->logger->format_log( $this->payment_id, 'PUT', 'Nets update reference', $request_args, $request_url, json_decode( wp_remote_retrieve_body( $response ), true ), $code );
		Nets_Easy()->logger->log( $log );

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

	/**
	 * Gets the request args for the API call.
	 *
	 * @return array
	 */
	public function get_request_args() {
		$request_args = array(
			'headers'    => $this->request_headers( $this->order_id ),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'PUT',
			'body'       => wp_json_encode( $this->request_body() ),
			'timeout'    => apply_filters( 'nets_easy_set_timeout', 10 ),
		);
		return $request_args;
	}

	/**
	 * Gets the request request body.
	 *
	 * @return array
	 */
	public function request_body() {
		return array(
			'reference'   => $this->order_number,
			'checkoutUrl' => wc_get_checkout_url(),
		);
	}

	/**
	 * Gets the order number for the order.
	 *
	 * @param string $order_id WC order id.
	 * @return string
	 */
	public function get_order_number( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( is_object( $order ) ) {
			// Make sure to run Sequential Order numbers if plugin exsists.
			if ( class_exists( 'WC_Seq_Order_Number_Pro' ) ) {
				$sequential = new WC_Seq_Order_Number_Pro();
				$sequential->set_sequential_order_number( $order_id );
				$reference = $sequential->get_order_number( $order->get_order_number(), $order );
			} elseif ( class_exists( 'WC_Seq_Order_Number' ) ) {
				$sequential = new WC_Seq_Order_Number();
				$sequential->set_sequential_order_number( $order_id, get_post( $order_id ) );
				$reference = $sequential->get_order_number( $order->get_order_number(), $order );
			} else {
				$reference = $order->get_order_number();
			}
		} else {
			$reference = $order_id;
		}
		return $reference;
	}
}
