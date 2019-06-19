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
				$this->backup_order_creation( $data );
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
			update_post_meta( $order_id, '_dibs_date_paid', date( 'Y-m-d H:i:s' ) );
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
		$dibs_order_total = $dibs_order['data']['order']['amount']['amount'];

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
	public function backup_order_creation( $dibs_checkout_completed_order ) {
		$request    = new DIBS_Requests_Get_DIBS_Order( $dibs_checkout_completed_order['data']['paymentId'] );
		$dibs_order = $request->request();

		// Process order.
		$order = $this->process_order( $dibs_order, $dibs_checkout_completed_order );

		// Send order number to DIBS
		if ( is_object( $order ) ) {
			$request = new DIBS_Requests_Update_DIBS_Order_Reference( $dibs_checkout_completed_order['data']['paymentId'], $order->get_id() );
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
	private function process_order( $dibs_order, $dibs_checkout_completed_order = null ) {

		if ( array_key_exists( 'name', $dibs_order->payment->consumer->company ) ) {
			$type     = 'company';
			$customer = $dibs_order->payment->consumer->company;
		} else {
			$type     = 'person';
			$customer = $dibs_order->payment->consumer->privatePerson;
		}

		$order = wc_create_order( array( 'status' => 'pending' ) );

		if ( is_wp_error( $order ) ) {
			DIBS_Easy::log( 'Backup order creation. Error - could not create order. ' . var_export( $order->get_error_message(), true ) );
		} else {
			DIBS_Easy::log( 'Backup order creation - order ID - ' . $order->get_id() . ' - created.' );
		}

		$order_id = $order->get_id();

		$billing_first_name   = ( 'person' === $type ) ? $customer->firstName : $customer->contactDetails->firstName;
		$billing_last_name    = ( 'person' === $type ) ? $customer->lastName : $customer->contactDetails->lastName;
		$billing_address1     = $dibs_order->payment->consumer->shippingAddress->addressLine1;
		$billing_postal_code  = $dibs_order->payment->consumer->shippingAddress->postalCode;
		$billing_city         = $dibs_order->payment->consumer->shippingAddress->city;
		$billing_country      = dibs_get_iso_2_country( $dibs_order->payment->consumer->shippingAddress->country );
		$phone                = ( 'person' === $type ) ? $customer->phoneNumber->number : $customer->contactDetails->phoneNumber->number;
		$email                = ( 'person' === $type ) ? $customer->email : $customer->contactDetails->email;
		$shipping_first_name  = ( 'person' === $type ) ? $customer->firstName : $customer->contactDetails->firstName;
		$shipping_last_name   = ( 'person' === $type ) ? $customer->lastName : $customer->contactDetails->firstName;
		$shipping_address1    = $dibs_order->payment->consumer->shippingAddress->addressLine1;
		$shipping_postal_code = $dibs_order->payment->consumer->shippingAddress->postalCode;
		$shipping_city        = $dibs_order->payment->consumer->shippingAddress->city;
		$shipping_country     = dibs_get_iso_2_country( $dibs_order->payment->consumer->shippingAddress->country );

		$order->set_billing_first_name( sanitize_text_field( $billing_first_name ) );
		$order->set_billing_last_name( sanitize_text_field( $billing_last_name ) );
		$order->set_billing_country( sanitize_text_field( $billing_country ) );
		$order->set_billing_address_1( sanitize_text_field( $billing_address1 ) );
		$order->set_billing_city( sanitize_text_field( $billing_city ) );
		$order->set_billing_postcode( sanitize_text_field( $billing_postal_code ) );
		$order->set_billing_phone( sanitize_text_field( $phone ) );
		$order->set_billing_email( sanitize_text_field( $email ) );
		$order->set_shipping_first_name( sanitize_text_field( $shipping_first_name ) );
		$order->set_shipping_last_name( sanitize_text_field( $shipping_last_name ) );
		$order->set_shipping_country( sanitize_text_field( $shipping_country ) );
		$order->set_shipping_address_1( sanitize_text_field( $shipping_address1 ) );
		$order->set_shipping_city( sanitize_text_field( $shipping_city ) );
		$order->set_shipping_postcode( sanitize_text_field( $shipping_postal_code ) );

		if ( 'company' === $type ) {
			$order->set_billing_company( sanitize_text_field( $customer->name ) );
			$order->set_shipping_company( sanitize_text_field( $customer->name ) );
		}

		if ( isset( $dibs_order->payment->consumer->shippingAddress->addressLine2 ) ) {
			$order->set_billing_address_2( sanitize_text_field( $dibs_order->payment->consumer->shippingAddress->addressLine2 ) );
			$order->set_shipping_address_2( sanitize_text_field( $dibs_order->payment->consumer->shippingAddress->addressLine2 ) );
		}

		$order->set_created_via( 'dibs_easy_api' );
		$order->set_currency( sanitize_text_field( $dibs_order->payment->orderDetails->currency ) );
		$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );

		$available_gateways = WC()->payment_gateways->payment_gateways();
		$payment_method     = $available_gateways['dibs_easy'];
		$order->set_payment_method( $payment_method );

		$this->process_order_lines( $dibs_checkout_completed_order, $order );

		$order->set_shipping_total( self::get_shipping_total( $dibs_checkout_completed_order ) );
		$order->set_cart_tax( self::get_cart_contents_tax( $dibs_checkout_completed_order ) );
		$order->set_shipping_tax( self::get_shipping_tax_total( $dibs_checkout_completed_order ) );
		$order->set_total( $dibs_checkout_completed_order['data']['order']['amount']['amount'] / 100 );

		$order->add_order_note( __( 'Order created via DIBS Easy API callback. Please verify the order in DIBS system.', 'dibs-easy-for-woocommerce' ) );

		// Make sure to run Sequential Order numbers if plugin exsists.
		if ( class_exists( 'WC_Seq_Order_Number_Pro' ) ) {
			$sequential = new WC_Seq_Order_Number_Pro();
			$sequential->set_sequential_order_number( $order_id );
		} elseif ( class_exists( 'WC_Seq_Order_Number' ) ) {
			$sequential = new WC_Seq_Order_Number();
			$sequential->set_sequential_order_number( $order_id, get_post( $order_id ) );
		}

		update_post_meta( $order_id, 'dibs_payment_type', $dibs_order->payment->paymentDetails->paymentType );
		update_post_meta( $order_id, '_dibs_payment_id', $dibs_order->payment->paymentId );
		update_post_meta( $order_id, '_dibs_date_paid', date( 'Y-m-d H:i:s' ) );

		if ( 'CARD' == $dibs_order->payment->paymentDetails->paymentType ) {
			update_post_meta( $order_id, 'dibs_customer_card', $dibs_order->payment->paymentDetails->cardDetails->maskedPan );
		}
		if ( 'A2A' === $dibs_order->payment->paymentDetails->paymentType ) {
			$order->add_order_note( sprintf( __( 'Order made in DIBS with Payment ID %1$s. Payment type - %2$s.', 'dibs-easy-for-woocommerce' ), $dibs_order->payment->paymentId, $dibs_order->payment->paymentDetails->paymentMethod ) );
		} else {
			$order->add_order_note( sprintf( __( 'Order made in DIBS with Payment ID %1$s. Payment type - %2$s.', 'dibs-easy-for-woocommerce' ), $dibs_order->payment->paymentId, $dibs_order->payment->paymentDetails->paymentType ) );
		}

		$order->calculate_totals();
		$order->save();

		if ( isset( $dibs_order->payment->summary->reservedAmount ) || isset( $dibs_order->payment->summary->chargedAmount ) || isset( $dibs_order->payment->subscription->id ) ) {
			$order->payment_complete( $dibs_order->payment->paymentId );
		}

		if ( (int) round( $order->get_total() * 100 ) !== (int) $dibs_checkout_completed_order['data']['order']['amount']['amount'] ) {
			$order->update_status( 'on-hold', sprintf( __( 'Order needs manual review, WooCommerce total and DIBS total do not match. DIBS order total: %s.', 'dibs-easy-for-woocommerce' ), $dibs_checkout_completed_order['data']['order']['amount']['amount'] ) );
		}

		return $order;
	}

	/**
	 * Processes cart contents on backup order creation.
	 *
	 * @param DIBS_Easy_Order   $dibs_order Klarna order.
	 * @param WooCommerce_Order $order WooCommerce order.
	 *
	 * @throws Exception WC_Data_Exception.
	 */
	private function process_order_lines( $dibs_checkout_completed_order, $order ) {
		DIBS_Easy::log( 'Processing order lines (from DIBS order) during backup order creation for DIBS payment ID ' . $dibs_checkout_completed_order['data']['paymentId'] );
		foreach ( $dibs_checkout_completed_order['data']['order']['orderItems'] as $cart_item ) {

			if ( strpos( $cart_item['reference'], 'shipping|' ) !== false ) {
				// Shipping
				$trimmed_cart_item_reference = str_replace( 'shipping|', '', $cart_item['reference'] );
				$method_id                   = substr( $trimmed_cart_item_reference, 0, strpos( $trimmed_cart_item_reference, ':' ) );
				$instance_id                 = substr( $trimmed_cart_item_reference, strpos( $trimmed_cart_item_reference, ':' ) + 1 );
				$rate                        = new WC_Shipping_Rate( $trimmed_cart_item_reference, $cart_item['name'], $cart_item['netTotalAmount'] / 100, array(), $method_id, $instance_id );
				$item                        = new WC_Order_Item_Shipping();
				$item->set_props(
					array(
						'method_title' => $rate->label,
						'method_id'    => $rate->id,
						'total'        => wc_format_decimal( $rate->cost ),
						'taxes'        => $rate->taxes,
						'meta_data'    => $rate->get_meta_data(),
					)
				);
				$order->add_item( $item );

			} elseif ( strpos( $cart_item['reference'], 'fee|' ) !== false ) {
				// Fee
				$trimmed_cart_item_id = str_replace( 'fee|', '', $cart_item['reference'] );
				$tax_class            = '';

				try {
					$args = array(
						'name'      => $cart_item['name'],
						'tax_class' => $tax_class,
						'subtotal'  => $cart_item['netTotalAmount'] / 100,
						'total'     => $cart_item['netTotalAmount'] / 100,
						'quantity'  => $cart_item['quantity'],
					);
					$fee  = new WC_Order_Item_Fee();
					$fee->set_props( $args );
					$order->add_item( $fee );
				} catch ( Exception $e ) {
					DIBS_Easy::log( 'Backup order creation error add fee error: ' . $e->getCode() . ' - ' . $e->getMessage() );
				}
			} else {
				// Product items
				if ( wc_get_product_id_by_sku( $cart_item['reference'] ) ) {
					$id = wc_get_product_id_by_sku( $cart_item['reference'] );
				} else {
					$id = $cart_item['reference'];
				}

				try {
					$product = wc_get_product( $id );

					$args = array(
						'name'         => $product->get_name(),
						'tax_class'    => $product->get_tax_class(),
						'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
						'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
						'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
						'subtotal'     => ( $cart_item['netTotalAmount'] ) / 100,
						'total'        => ( $cart_item['netTotalAmount'] ) / 100,
						'quantity'     => $cart_item['quantity'],
					);
					$item = new WC_Order_Item_Product();
					$item->set_props( $args );
					$item->set_backorder_meta();
					$item->set_order_id( $order->get_id() );
					$item->calculate_taxes();
					$item->save();
					$order->add_item( $item );
				} catch ( Exception $e ) {
					DIBS_Easy::log( 'Backup order creation error add to cart error: ' . $e->getCode() . ' - ' . $e->getMessage() );
				}
			}
		}
	}

	private static function get_shipping_total( $dibs_checkout_completed_order ) {
		$shipping_total = 0;
		foreach ( $dibs_checkout_completed_order['data']['order']['orderItems'] as $cart_item ) {
			if ( strpos( $cart_item['reference'], 'shipping|' ) !== false ) {
				$shipping_total += $cart_item['grossTotalAmount'];
			}
		}
		if ( $shipping_total > 0 ) {
			$shipping_total = $shipping_total / 100;
		}
		return $shipping_total;
	}

	private static function get_cart_contents_tax( $dibs_checkout_completed_order ) {
		$cart_contents_tax = 0;
		foreach ( $dibs_checkout_completed_order['data']['order']['orderItems'] as $cart_item ) {
			if ( strpos( $cart_item['reference'], 'shipping|' ) === false && strpos( $cart_item['reference'], 'fee|' ) === false ) {
				$cart_contents_tax += $cart_item['taxAmount'];
			}
		}
		if ( $cart_contents_tax > 0 ) {
			$cart_contents_tax = $cart_contents_tax / 100;
		}
		return $cart_contents_tax;
	}

	private static function get_shipping_tax_total( $dibs_checkout_completed_order ) {
		$shipping_tax_total = 0;
		foreach ( $dibs_checkout_completed_order['data']['order']['orderItems'] as $cart_item ) {
			if ( strpos( $cart_item['reference'], 'shipping|' ) !== false ) {
				$shipping_tax_total += $cart_item['taxAmount'];
			}
		}
		if ( $shipping_tax_total > 0 ) {
			$shipping_tax_total = $shipping_tax_total / 100;
		}
		return $shipping_tax_total;
	}

}
DIBS_Api_Callbacks::get_instance();
