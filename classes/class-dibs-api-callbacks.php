<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * DIBS_Api_Callbacks class.
 *
 * @since 1.4.0
 *
 * Class that handles DIBS API callbacks.
 */
class DIBS_Api_Callbacks {

	/**
	 * The reference the *Singleton* instance of this class.
	 *
	 * @var $instance
	 */
	protected static $instance;
	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return self::$instance The *Singleton* instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * DIBS_Api_Callbacks constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_api_dibs_api_callbacks', array( $this, 'payment_created_scheduler' ) );
		add_action( 'dibs_payment_created_callback', array( $this, 'execute_dibs_payment_created_callback' ), 10, 2 );

	}

	public function payment_created_scheduler() {
		if ( isset( $_GET['dibs-payment-created-callback'] ) ) {

			$post_body = file_get_contents( 'php://input' );
			$data      = json_decode( $post_body, true );

			// Order id is set to '' for now
			$order_id = '';

			wp_schedule_single_event( time() + 120, 'dibs_payment_created_callback', array( $data, $order_id ) );
		}
	}

	public function execute_dibs_payment_created_callback( $data, $order_id = '' ) {

		DIBS_Easy::log( 'Payment created API callback. Response data:' . json_encode( $data ) );
		if ( empty( $order_id ) ) { // We're missing Order ID in callback. Try to get it via query by internal reference
			$order_id = $this->get_order_id_from_payment_id( $data['data']['paymentId'] );
		}

		if ( ! empty( $order_id ) ) { // Input var okay.

			$this->check_order_status( $data, $order_id );

		} else { // We can't find a coresponding Order ID.

			DIBS_Easy::log( 'No coresponding order ID was found for Payment ID ' . $data['data']['paymentId'] );
			// Backup order creation
			if ( ! empty( $data['data']['paymentId'] ) ) {
				// @todo Check webhook API issue before releasing this feature
				$this->backup_order_creation( $data['data']['paymentId'] );
			}
		} // End if().
	}


	/**
	 * Try to retreive order_id from DIBS transaction id.
	 *
	 * @param string $internal_reference.
	 */
	public function get_order_id_from_payment_id( $payment_id ) {

		// Let's check so the internal reference doesn't already exist in an existing order
		$query  = new WC_Order_Query(
			array(
				'limit'          => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'return'         => 'ids',
				'payment_method' => 'dibs_easy',
				'date_created'   => '>' . ( time() - MONTH_IN_SECONDS ),
			)
		);
		$orders = $query->get_orders();

		$order_id_match = '';
		foreach ( $orders as $order_id ) {

			$order_payment_id = get_post_meta( $order_id, '_dibs_payment_id', true );

			if ( $order_payment_id === $payment_id ) {
				$order_id_match = $order_id;
				DIBS_Easy::log( 'Payment ID ' . $payment_id . ' already exist in order ID ' . $order_id_match );
				break;
			}
		}
		return $order_id_match;
	}


	/**
	 * Check order status order total and transaction id, in case checkout process failed.
	 *
	 * @param string $private_id, $public_token, $customer_type.
	 *
	 * @throws Exception WC_Data_Exception.
	 */
	public function check_order_status( $data, $order_id ) {
		$order = wc_get_order( $order_id );

		if ( is_object( $order ) ) {
			// Check order status
			if ( ! $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
				$order_totals_match = $this->check_order_totals( $order, $data );

				// Set order status in Woo
				if ( true === $order_totals_match ) {
					$this->set_order_status( $order, $data );
				}
			}
		}
	}


	/**
	 * Set order status function
	 */
	public function set_order_status( $order, $data ) {
		if ( $data['data']['paymentId'] ) {
			$order->payment_complete( $data['data']['paymentId'] );
			$order->add_order_note( 'Payment via DIBS Easy. Order status updated via API callback. Payment ID: ' . sanitize_key( $data['data']['paymentId'] ) );
			DIBS_Easy::log( 'Order status not set correctly for order ' . $order->get_order_number() . ' during checkout process. Setting order status to Processing/Completed in API callback.' );
		} else {

			// DIBS_Easy::log('Order status not set correctly for order ' . $order->get_order_number() . ' during checkout process. Setting order status to On hold.');
		}
	}

	/**
	 * Check order totals
	 */
	public function check_order_totals( $order, $dibs_order ) {

		$order_totals_match = true;

		// Check order total and compare it with Woo
		$woo_order_total  = intval( round( $order->get_total() ) * 100 );
		$dibs_order_total = $dibs_order['data']['amount']['amount'];

		if ( $woo_order_total > $dibs_order_total && ( $woo_order_total - $dibs_order_total ) > 30 ) {
			$order->update_status( 'on-hold', sprintf( __( 'Order needs manual review. WooCommerce order total and DIBS order total do not match. DIBS order total: %s.', 'dibs-easy-for-woocommerce' ), $dibs_order_total ) );
			DIBS_Easy::log( 'Order total missmatch in order:' . $order->get_order_number() . '. Woo order total: ' . $woo_order_total . '. DIBS order total: ' . $dibs_order_total );
			$order_totals_match = false;
		} elseif ( $dibs_order_total > $woo_order_total && ( $dibs_order_total - $woo_order_total ) > 30 ) {
			$order->update_status( 'on-hold', sprintf( __( 'Order needs manual review. WooCommerce order total and DIBS order total do not match. DIBS order total: %s.', 'dibs-easy-for-woocommerce' ), $dibs_order_total ) );
			DIBS_Easy::log( 'Order total missmatch in order:' . $order->get_order_number() . '. Woo order total: ' . $woo_order_total . '. DIBS order total: ' . $dibs_order_total );
			$order_totals_match = false;
		}

		return $order_totals_match;

	}

	/**
	 * Backup order creation, in case checkout process failed.
	 *
	 * @param string $klarna_order_id Klarna order ID.
	 *
	 * @throws Exception WC_Data_Exception.
	 */
	public function backup_order_creation( $payment_id ) {
		$request    = new DIBS_Requests_Get_DIBS_Order( $payment_id );
		$dibs_order = $request->request();

		// Process order.
		$order = $this->process_order( $dibs_order );

		// Send order number to DIBS
		if ( is_object( $order ) ) {
			$request = new DIBS_Requests_Update_DIBS_Order_Reference( $payment_id, $order->get_id() );
			$request = $request->request();
		}
	}

	/**
	 * Processes WooCommerce order on backup order creation.
	 *
	 * @param Klarna_Checkout_Order $collector_order Klarna order.
	 *
	 * @throws Exception WC_Data_Exception.
	 */
	private function process_order( $dibs_order ) {

		if ( array_key_exists( 'name', $dibs_order->payment->consumer->company ) ) {
			$type     = 'company';
			$customer = $dibs_order->payment->consumer->company;
		} else {
			$type     = 'person';
			$customer = $dibs_order->payment->consumer->privatePerson;
		}

		$order = wc_create_order( array( 'status' => 'failed' ) );

		if ( is_wp_error( $order ) ) {
			DIBS_Easy::log( 'Backup order creation. Error - could not create order. ' . var_export( $order->get_error_message(), true ) );
		} else {
			DIBS_Easy::log( 'Backup order creation - order ID - ' . $order->get_id() . ' - created.' );
		}

		$order_id = $order->get_id();

		update_post_meta( $order_id, '_billing_first_name', ( 'person' === $type ) ? $customer->firstName : $customer->contactDetails->firstName );
		update_post_meta( $order_id, '_billing_last_name', ( 'person' === $type ) ? $customer->lastName : $customer->contactDetails->lastName );
		update_post_meta( $order_id, '_billing_address_1', $dibs_order->payment->consumer->shippingAddress->addressLine1 );
		update_post_meta( $order_id, '_billing_city', $dibs_order->payment->consumer->shippingAddress->city );
		update_post_meta( $order_id, '_billing_postcode', $dibs_order->payment->consumer->shippingAddress->postalCode );
		update_post_meta( $order_id, '_billing_country', dibs_get_iso_2_country( $dibs_order->payment->consumer->shippingAddress->country ) );
		update_post_meta( $order_id, '_billing_phone', ( 'person' === $type ) ? $customer->phoneNumber->number : $customer->contactDetails->phoneNumber->number );
		update_post_meta( $order_id, '_billing_email', ( 'person' === $type ) ? $customer->email : $customer->contactDetails->email );
		update_post_meta( $order_id, '_shipping_first_name', ( 'person' === $type ) ? $customer->firstName : $customer->contactDetails->firstName );
		update_post_meta( $order_id, '_shipping_last_name', ( 'person' === $type ) ? $customer->lastName : $customer->contactDetails->firstName );
		update_post_meta( $order_id, '_shipping_address_1', $dibs_order->payment->consumer->shippingAddress->addressLine1 );
		update_post_meta( $order_id, '_shipping_city', $dibs_order->payment->consumer->shippingAddress->city );
		update_post_meta( $order_id, '_shipping_postcode', $dibs_order->payment->consumer->shippingAddress->postalCode );
		update_post_meta( $order_id, '_shipping_country', dibs_get_iso_2_country( $dibs_order->payment->consumer->shippingAddress->country ) );

		if ( 'company' === $type ) {
			update_post_meta( $order_id, '_billing_company', $customer->name );
			update_post_meta( $order_id, '_shipping_company', $customer->name );
		}

		if ( isset( $dibs_order->payment->consumer->shippingAddress->addressLine2 ) ) {
			update_post_meta( $order_id, '_billing_address_2', $dibs_order->payment->consumer->shippingAddress->addressLine2 );
			update_post_meta( $order_id, '_shipping_address_2', $dibs_order->payment->consumer->shippingAddress->addressLine2 );
		}

		$order->set_created_via( 'dibs_easy_api' );
		$order->set_currency( sanitize_text_field( $dibs_order->payment->orderDetails->currency ) );
		$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );

		$available_gateways = WC()->payment_gateways->payment_gateways();
		$payment_method     = $available_gateways['dibs_easy'];
		$order->set_payment_method( $payment_method );
		$order->add_order_note( __( 'Something went wrong during WooCommerce checkout process. This order was created as a fallback via DIBS Easy API callback. Order product information not available. Please see the order in DIBS system for more information about products and order amount.', 'dibs-easy-for-woocommerce' ) );

		// Make sure to run Sequential Order numbers if plugin exsists.
		if ( class_exists( 'WC_Seq_Order_Number_Pro' ) ) {
			$sequential = new WC_Seq_Order_Number_Pro();
			$sequential->set_sequential_order_number( $order_id );
		} elseif ( class_exists( 'WC_Seq_Order_Number' ) ) {
			$sequential = new WC_Seq_Order_Number();
			$sequential->set_sequential_order_number( $order_id, get_post( $order_id ) );
		}

		update_post_meta( $order_id, 'dibs_payment_type', $dibs_order->payment->paymentDetails->paymentType );
		update_post_meta( $order_id, '_transaction_id', $dibs_order->payment->paymentId );

		if ( 'CARD' == $dibs_order->payment->paymentDetails->paymentType ) {
			update_post_meta( $order_id, 'dibs_customer_card', $dibs_order->payment->paymentDetails->cardDetails->maskedPan );
		}

		$order->add_order_note( sprintf( __( 'Purchase via %s', 'dibs-easy-for-woocommerce' ), $dibs_order->payment->paymentDetails->paymentType ) );

		$order->save();

		return $order;
	}

}
DIBS_Api_Callbacks::get_instance();
