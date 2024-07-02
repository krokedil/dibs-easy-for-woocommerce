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
 * Nets_Easy_API class.
 *
 * Class that has functions for the Nexi Checkout communication.
 */
class Nets_Easy_API {


	/**
	 * Creates a Nexi Checkout order.
	 *
	 * @param array $args Data passed to init request.
	 *
	 * @return array|mixed
	 */
	public function create_nets_easy_order( $args = array() ) {

		$request  = new Nets_Easy_Request_Create_Order( $args );
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
	public function update_nets_easy_order( $payment_id ) {
		$request  = new Nets_Easy_Request_Update_Order( array( 'payment_id' => $payment_id ) );
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
	public function update_nets_easy_order_reference( $payment_id, $order_id = null ) {
		$request = new Nets_Easy_Request_Update_Order_Reference(
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
	public function cancel_nets_easy_order( $order_id ) {
		$request  = new Nets_Easy_Request_Cancel_Order(
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
	public function refund_nets_easy_order( $order_id ) {
		$request  = new Nets_Easy_Request_Refund_Order(
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
	 *
	 * @return array|mixed
	 */
	public function get_nets_easy_order( $payment_id ) {
		$request  = new Nets_Easy_Request_Get_Order(
			array(
				'payment_id' => $payment_id,
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
	public function activate_nets_easy_order( $order_id ) {
		$request  = new Nets_Easy_Request_Activate_Order(
			array(
				'order_id' => $order_id,
			)
		);
		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 * Charge scheduled subscription.
	 *
	 * @param int    $order_id The WooCommerce order id.
	 * @param string $recurring_token Subscription token.
	 *
	 * @return array|mixed
	 */
	public function charge_nets_easy_scheduled_subscription( $order_id, $recurring_token ) {
		$request  = new Nets_Easy_Request_Charge_Subscription(
			array(
				'order_id'        => $order_id,
				'recurring_token' => $recurring_token,
			)
		);
		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 * Charge unscheduled subscription.
	 *
	 * @param int    $order_id The WooCommerce order id.
	 * @param string $recurring_token Subscription token.
	 *
	 * @return array|mixed
	 */
	public function charge_nets_easy_unscheduled_subscription( $order_id, $recurring_token ) {
		$request  = new Nets_Easy_Request_Charge_Unscheduled_Subscription(
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
	public function get_nets_easy_subscription( $subscription_id, $order_id ) {
		$request  = new Nets_Easy_Request_Get_Subscription(
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
	public function get_nets_easy_subscription_bulk_charge_id( $bulk_id ) {
		$request  = new Nets_Easy_Request_Get_Subscription_Bulk_Charge_Id(
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
	public function get_nets_easy_subscription_by_external_reference( $dibs_ticket, $order_id ) {
		$request  = new Nets_Easy_Request_Get_Subscription_By_External_Reference(
			array(
				'external_reference' => $dibs_ticket,
				'order_id'           => $order_id,
			)
		);
		$response = $request->request();
		return $this->check_for_api_error( $response );
	}

	/**
	 * Retrieves a unscheduled subscription by external reference
	 *
	 * @param string $dibs_ticket The external reference to search for.
	 * @param int    $order_id The WooCommerce order id.
	 *
	 * @return array|mixed
	 */
	public function get_nets_easy_unscheduled_subscription_by_external_reference( $dibs_ticket, $order_id ) {
		$request  = new Nets_Easy_Request_Get_Unscheduled_Subscription_By_External_Reference(
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
	 * @param array|WP_Error $response The response from the request.
	 * @return mixed
	 */
	private function check_for_api_error( $response ) {
		if ( is_wp_error( $response ) && ! is_admin() ) {
			dibs_easy_print_error_message( $response );
		}
		return $response;
	}
}
