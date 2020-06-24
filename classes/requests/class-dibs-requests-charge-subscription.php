<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DIBS_Request_Charge_Subscription extends DIBS_Requests2 {

	public $order_id;

	public function __construct( $order_id ) {
		parent::__construct();

		$this->order_id = $order_id;
	}

	public function request() {

		$request_url = $this->endpoint . 'subscriptions/charges';

		$response = wp_remote_request( $request_url, $this->get_request_args() );
		if ( is_wp_error( $response ) ) {
			return $this->get_error_message( $response );
			// return 'ERROR';
		}

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			DIBS_Easy::log( 'DIBS Charge subscription request response: ' . stripslashes_deep( json_encode( $response ) ) );
			return json_decode( wp_remote_retrieve_body( $response ) );
		} else {
			return $this->get_error_message( $response );
			// return 'ERROR';
		}
	}

	public function get_request_args() {
		$request_args = array(
			'headers'    => $this->request_headers( $this->order_id ),
			'user-agent' => $this->request_user_agent(),
			'method'     => 'POST',
			'body'       => json_encode( $this->request_body() ),
			'timeout'    => apply_filters( 'nets_easy_set_timeout', 10 ),
		);
		DIBS_Easy::log( 'DIBS Charge Subscription request args: ' . json_encode( $request_args ) );
		return apply_filters( 'dibs_easy_charge_subscription_args', $request_args );
	}

	public function request_body() {
		$order = wc_get_order( $this->order_id );

		$subscriptions = wcs_get_subscriptions_for_renewal_order( $this->order_id );
		reset( $subscriptions );
		$subscription_id = key( $subscriptions );

		$recurring_token = get_post_meta( $this->order_id, '_dibs_recurring_token', true );
		if ( empty( $recurring_token ) ) {
			$recurring_token = get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $this->order_id ), '_dibs_recurring_token', true );
			update_post_meta( $this->order_id, '_dibs_recurring_token', $recurring_token );
		}

		$body                                        = array();
		$body['externalBulkChargeId']                = $order->get_order_number() . '-' . time();
		$body['subscriptions'][0]['subscriptionId']  = $recurring_token;
		$body['subscriptions'][0]['order']['items']  = DIBS_Requests_Get_Order_Items::get_items( $this->order_id );
		$body['subscriptions'][0]['order']['amount'] = $order->get_total() * 100;
		$body['subscriptions'][0]['order']['currency']  = $order->get_currency();
		$body['subscriptions'][0]['order']['reference'] = $order->get_order_number();

		return $body;
	}
}
