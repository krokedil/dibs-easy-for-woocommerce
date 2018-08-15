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
		add_action( 'woocommerce_api_dibs_wc_payment_created', array( $this, 'payment_created_scheduler' ) );
		add_action( 'dibs_payment_created_callback', array( $this, 'execute_dibs_payment_created_callback' ), 10, 2 );
		

	}

    public function payment_created_scheduler() {
        $post_body = file_get_contents( 'php://input' );
		$data      = json_decode( $post_body, true );
		
		// Order id is set to '' for now
		$order_id = '';

		wp_schedule_single_event( time() + 120, 'dibs_payment_created_callback', array( $data, $order_id ) );
        
	}

	public function execute_dibs_payment_created_callback( $data, $order_id = '' ) {
		
		DIBS_Easy::log( 'Payment created API callback. Response data:' . json_encode( $data ) );
		
		if( empty( $order_id ) ) { // We're missing Order ID in callback. Try to get it via query by internal reference
			$order_id = $this->get_order_id_from_payment_id( $data['data']['paymentId'] );
		}
		
		if ( !empty( $order_id ) ) { // Input var okay.
			
			$this->check_order_status( $data, $order_id );
			
		} else { // We can't find a coresponding Order ID.
			
			DIBS_Easy::log( 'No coresponding order ID was found for Payment ID ' . $data['data']['paymentId'] );
			// @todo - add create order fallback process.
			
		} // End if().	    
	}

	
	/**
	 * Try to retreive order_id from DIBS transaction id.
	 *
	 * @param string $internal_reference.
	 *
	 */
	public function get_order_id_from_payment_id( $payment_id ) {
	
		// Let's check so the internal reference doesn't already exist in an existing order
		$query = new WC_Order_Query( array(
	        'limit' => -1,
	        'orderby' => 'date',
	        'order' => 'DESC',
	        'return' => 'ids',
	        'payment_method' => 'dibs_easy',
	        'date_created' => '>' . ( time() - MONTH_IN_SECONDS )
	    ) );
		$orders = $query->get_orders();
		
		$order_id_match = '';
	    foreach( $orders as $order_id ) {
			
			$order_payment_id = get_post_meta( $order_id, '_dibs_payment_id', true );
			
	        if( $order_payment_id === $payment_id ) {
	            $order_id_match = $order_id;
	            DIBS_Easy::log('Payment ID ' . $payment_id . ' already exist in order ID ' . $order_id_match);
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
		
		if( is_object( $order ) ) {
			// Check order status
			if ( ! $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
				$order_totals_match = $this->check_order_totals( $order, $data );
				
				// Set order status in Woo
				if( true === $order_totals_match ) {
					$this->set_order_status( $order, $data );
				}
			}
		}
	}

	
	/**
	 * Set order status function
	 *
	 */
	public function set_order_status( $order, $data ) {
		if ( $data['data']['paymentId'] ) {
			$order->payment_complete( $data['data']['paymentId'] );
			$order->add_order_note( 'Payment via DIBS Easy. Order status updated via API callback. Payment ID: ' . sanitize_key( $data['data']['paymentId'] ) );
			DIBS_Easy::log('Order status not set correctly for order ' . $order->get_order_number() . ' during checkout process. Setting order status to Processing/Completed in API callback.');
		} else {
	
			//DIBS_Easy::log('Order status not set correctly for order ' . $order->get_order_number() . ' during checkout process. Setting order status to On hold.');
		}
	}

	/**
	 * Check order totals
	 *
	 */
	public function check_order_totals( $order, $dibs_order ) {

		$order_totals_match = true;

		// Check order total and compare it with Woo
		$woo_order_total = intval( round( $order->get_total() ) * 100 );
		$dibs_order_total = $dibs_order['data']['amount']['amount'];
		
		if( $woo_order_total > $dibs_order_total && ( $woo_order_total - $dibs_order_total ) > 30 ) {
			$order->update_status( 'on-hold',  sprintf(__( 'Order needs manual review. WooCommerce order total and DIBS order total do not match. DIBS order total: %s.', 'dibs-easy-for-woocommerce' ), $dibs_order_total ) );
			DIBS_Easy::log('Order total missmatch in order:' . $order->get_order_number() . '. Woo order total: ' . $woo_order_total . '. DIBS order total: ' . $dibs_order_total );
			$order_totals_match = false;
		} elseif( $dibs_order_total > $woo_order_total && ( $dibs_order_total - $woo_order_total ) > 30 ) {
			$order->update_status( 'on-hold',  sprintf(__( 'Order needs manual review. WooCommerce order total and DIBS order total do not match. DIBS order total: %s.', 'dibs-easy-for-woocommerce' ), $dibs_order_total ) );
			DIBS_Easy::log('Order total missmatch in order:' . $order->get_order_number() . '. Woo order total: ' . $woo_order_total . '. DIBS order total: ' . $dibs_order_total );
			$order_totals_match = false;
		}

		return $order_totals_match;
	
	}
	 
}
DIBS_Api_Callbacks::get_instance();