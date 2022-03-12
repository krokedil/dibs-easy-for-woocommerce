<?php
/**
 * API Class file.
 *
 * @package Dibs_Easy_For_WooCommerce/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dibs_Easy_One_API class.
 *
 * Class that has functions for the Qliro One communication.
 */
class Dibs_Easy_API {


	/**
	 * Creates a Dibs Easy One Checkout order.
	 *
	 * @param string $checkout_flow Checkout type.
	 * @param int    $order_id The WooCommerce order id.
	 *
	 * @return array|mixed
	 */
	public function create_dibs_easy_order( $checkout_flow = 'embeded', $order_id = null ) {

		// todo create dibs order.
		$request  = new DIBS_Requests_Create_DIBS_Order(
			array(
				'checkout_flow' => $checkout_flow,
				'order_id'      => $order_id,
			)
		);
		$response = $request->request();

		return $this->check_for_api_error( $response );

	}

	/**
	 * Updates a Dibs Easy One order.
	 *
	 * @param string $payment_id The payment identifier.
	 *
	 * @return array|mixed
	 */
	public function update_dibs_easy_order( $payment_id ) {
		$request  = new DIBS_Requests_Update_DIBS_Order( array( 'payment_id' => $payment_id ) );
		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 * Update reference information
	 *
	 * @param string $payment_id The payment identifier.
	 * @param int    $order_id The WooCommerce order id.
	 *
	 * @return array|mixed
	 */
	public function update_dibs_easy_order_reference( $payment_id, $order_id = null ) {
		$request = new DIBS_Requests_Update_DIBS_Order_Reference(
			array(
				'payment_id' => $payment_id,
				'order_id'   => $order_id,
			)
		);

		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 * Cancels Dibs Easy order.
	 *
	 * @param int $order_id The WooCommerce order id.
	 *
	 * @return array|mixed
	 */
	public function cancel_dibs_easy_order( $order_id ) {
		$request  = new DIBS_Requests_Cancel_Order(
			array(
				'order_id' => $order_id,
			)
		);
		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 *
	 * Refunds Dibs Easy order.
	 *
	 * @param int $order_id The WooCommerce order id.
	 *
	 * @return array|mixed
	 */
	public function refund_dibs_easy_order( $order_id ) {
		$request  = new DIBS_Request_Refund_Order(
			array(
				'order_id' => $order_id,
			)
		);
		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 * Retrieves Dibs Easy order.
	 *
	 * @param string $payment_id The payment identifier.
	 * @param int    $order_id The WooCommerce order id.
	 *
	 * @return array|mixed
	 */
	public function get_dibs_easy_order( $payment_id, $order_id ) {
		$request  = new DIBS_Requests_Get_DIBS_Order(
			array(
				'payment_id' => $payment_id,
				'order_id'   => $order_id,
			)
		);
		$response = $request->request();
		return $this->check_for_api_error( $response );

	}

	/**
	 * Activate Dibs Easy Order.
	 *
	 * @param int $order_id The WooCommerce order id.
	 *
	 * @return array|mixed
	 */
	public function activate_dibs_easy_order( $order_id ) {
		$request  = new DIBS_Requests_Activate_Order(
			array(
				'order_id' => $order_id,
			)
		);
		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 * Charge subscription.
	 *
	 * @param int    $order_id The WooCommerce order id.
	 * @param string $recurring_token Subscription token.
	 *
	 * @return array|mixed
	 */
	public function charge_dibs_easy_subscription( $order_id, $recurring_token ) {
		$request  = new DIBS_Request_Charge_Subscription(
			array(
				'order_id'        => $order_id,
				'recurring_token' => $recurring_token,
			)
		);
		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 * Retrieves an existing subscription by a subscriptionId.
	 *
	 * @param string $subscription_id The subscription identifier.
	 * @param int    $order_id The WooCommerce order id.
	 *
	 * @return array|mixed
	 */
	public function get_dibs_easy_subscription( $subscription_id, $order_id ) {
		$request  = new DIBS_Requests_Get_Subscription(
			array(
				'subscription_id' => $subscription_id,
				'order_id'        => $order_id,
			)
		);
		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 * Retrieves charges associated with the specified bulk charge operation.
	 *
	 * @param string $bulk_id The identifier of the bulk charge operation.
	 *
	 * @return array|mixed
	 */
	public function get_dibs_easy_subscription_bulk_charge_id( $bulk_id ) {
		$request  = new DIBS_Request_Get_Subscription_Bulk_Id(
			array(
				'bulk_id' => $bulk_id,
			)
		);
		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 * Retrieves a subscription by external reference
	 *
	 * @param string $dibs_ticket The external reference to search for.
	 * @param int    $order_id The WooCommerce order id.
	 *
	 * @return array|mixed
	 */
	public function get_dibs_easy_subscription_by_external_reference( $dibs_ticket, $order_id ) {
		$request  = new DIBS_Request_Get_Subscription_By_External_Reference(
			array(
				'external_reference' => $dibs_ticket,
				'order_id'           => $order_id,
			)
		);
		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 * Checks for WP Errors and returns either the response as array or a false.
	 *
	 * @param array $response The response from the request.
	 * @return mixed
	 */
	private function check_for_api_error( $response ) {
		if ( is_wp_error( $response ) && ! is_admin() ) {
			dibs_easy_print_error_message( $response );
		}
		return $response;
	}
}