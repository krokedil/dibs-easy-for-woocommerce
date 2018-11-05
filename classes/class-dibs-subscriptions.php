<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles subscription payments with Klarna checkout.
 *
 * @class    DIBS_Subscriptions
 * @version  1.0
 * @package  DIBS/Classes
 * @category Class
 * @author   Krokedil
 */
class DIBS_Subscriptions {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'dibs_easy_create_order_args', array( $this, 'maybe_add_subscription' ), 9, 1 );
		add_action( 'dibs_easy_process_payment', array( $this, 'set_recurring_token_for_order' ), 10, 2 );

		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'show_recurring_token' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_dibs_recurring_token_update' ), 45, 2 );

		// Maybe create user
		// @todo - keep working on this or remove it when we get the new order submission flow.
		// add_action( 'dibs_prepare_local_order_before_form_processing', array( $this, 'maybe_assign_or_create_user' ) );
		// add_action( 'dibs_easy_process_payment', array( $this, 'maybe_assign_or_create_user' ) );
		// Charge renewal payment
		add_action( 'woocommerce_scheduled_subscription_payment_dibs_easy', array( $this, 'trigger_scheduled_payment' ), 10, 2 );

		add_action( 'wc_dibs_easy_check_subscription_status', array( $this, 'check_subscription_status' ), 10, 2 );

	}



	/**
	 * Assigns an order to a user. Needed for Subscriptions.
	 *
	 * @param array $order_id The WooCommerce order ID.
	 * @return array
	 */
	public function maybe_assign_or_create_user( $order_id ) {
		// Check if we have a subscription product. If yes set check if we need to assign or create customer to order.
		if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $order_id ) ) {
			$order = wc_get_order( $order_id );

			if ( email_exists( $order->get_billing_email() ) ) {
				// Email exist in WP
				if ( ! $order->get_customer_id() ) {
					// No customer was assigned to the order - let's set it now.
					$user        = get_user_by( 'email', $order->get_billing_email() );
					$customer_id = $user->ID;
					$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', $customer_id ) );
					$order->save();
				}
			} else {
				// Email does not exist - lets create the customer
				// Generate username - force create user name even if get_option( 'woocommerce_registration_generate_username' ) is set to no.
				$username = sanitize_user( current( explode( '@', $order->get_billing_email() ) ), true );
				// Ensure username is unique.
				$append     = 1;
				$o_username = $username;
				while ( username_exists( $username ) ) {
					$username = $o_username . $append;
					$append++;
				}
				$customer_id = wc_create_new_customer( $order->get_billing_email(), $username, wp_generate_password() );
				$order->set_customer_id( apply_filters( 'woocommerce_checkout_customer_id', $customer_id ) );
				$order->save();
			}
		} else {
		}
	}

	/**
	 * Marks the order as a recurring order for Klarna
	 *
	 * @param array $request_args The Klarna request arguments.
	 * @return array
	 */
	public function maybe_add_subscription( $request_args ) {
		// Check if we have a subscription product. If yes set recurring field.
		if ( class_exists( 'WC_Subscriptions_Cart' ) && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			$request_args['subscription'] = array(
				'endDate'  => date( 'Y-m-d\TH:i', strtotime( '+150 year' ) ),
				'interval' => 0,
			);
		}
		return $request_args;
	}

	/**
	 * Sets the recurring token for the subscription order
	 *
	 * @return void
	 */
	public function set_recurring_token_for_order( $order_id, $dibs_order ) {
		$wc_order = wc_get_order( $order_id );
		if ( isset( $dibs_order->payment->subscription->id ) ) {
			update_post_meta( $order_id, '_dibs_recurring_token', $dibs_order->payment->subscription->id );

			$dibs_subscription = new DIBS_Requests_Get_Subscription( $dibs_order->payment->subscription->id );
			$request           = $dibs_subscription->request();
			if ( 'CARD' == $request->paymentDetails->paymentType ) {
				update_post_meta( $order_id, 'dibs_payment_type', $request->paymentDetails->paymentType );
				update_post_meta( $order_id, 'dibs_customer_card', $request->paymentDetails->cardDetails->maskedPan );
			}

			// This function is run after WCS has created the subscription order.
			// Let's add the _dibs_recurring_token to the subscription as well.
			if ( class_exists( 'WC_Subscription' ) && wcs_order_contains_subscription( $wc_order ) ) {
				$subcriptions = wcs_get_subscriptions_for_order( $order_id );
				foreach ( $subcriptions as $subcription ) {
					update_post_meta( $subcription->get_id(), '_dibs_recurring_token', $dibs_order->payment->subscription->id );
				}
			}
		}

		return $dibs_order;
	}

	/**
	 * Creates an order in DIBS from the recurring token saved.
	 *
	 * @param string $renewal_total The total price for the order.
	 * @param object $renewal_order The WooCommerce order for the renewal.
	 */
	public function trigger_scheduled_payment( $renewal_total, $renewal_order ) {
		$order_id = $renewal_order->get_id();

		// Get recurring token
		$recurring_token = get_post_meta( $order_id, '_dibs_recurring_token', true );
		if ( empty( $recurring_token ) ) {
			$recurring_token = get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_dibs_recurring_token', true );
			update_post_meta( $order_id, '_dibs_recurring_token', $recurring_token );
		}

		$create_order_response = new DIBS_Request_Charge_Subscription( $order_id );
		$create_order_response = $create_order_response->request();

		if ( ! empty( $create_order_response->bulkId ) ) {
			// We got a bulkId in response. Save it in the renewal order and make a new request to DIBS to get the status and ID of the transaction
			update_post_meta( $order_id, '_dibs_recurring_bulk_id', $create_order_response->bulkId );

			$recurring_orders = new DIBS_Request_Get_Subscription_Bulk_Id( $create_order_response->bulkId );
			$recurring_orders = $recurring_orders->request();

			$payment_id = null;
			foreach ( $recurring_orders->page as $recurring_order ) {
				if ( $recurring_order->subscriptionId == $recurring_token ) {
					$payment_id = $recurring_order->paymentId;
					break;
				}
			}

			if ( ! empty( $payment_id ) ) {

				if ( 'Succeeded' == $recurring_order->status ) {
					WC_Subscriptions_Manager::process_subscription_payments_on_order( $renewal_order );
					$renewal_order->add_order_note( sprintf( __( 'Subscription payment made with DIBS. DIBS order id: %s', 'dibs-easy-for-woocommerce' ), $payment_id ) );
					$renewal_order->payment_complete( $payment_id );
				} else {
					$renewal_order->add_order_note( sprintf( __( 'Payment status not available: Scheduling payment status check to be triggered in 1 minute.', 'dibs-easy-for-woocommerce' ), $payment_id ) );
					wp_schedule_single_event( time() + 60, 'wc_dibs_easy_check_subscription_status', array( $order_id, $create_order_response->bulkId ) );
				}
			} else {
				WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $renewal_order );
				$renewal_order->add_order_note( sprintf( __( 'Subscription payment failed with DIBS. Error code: %1$s. Message: %2$s', 'dibs-easy-for-woocommerce' ), 'fel', $create_order_response->bulkId ) );
			}
		}

		// error_log('$create_order_response ' . var_export($create_order_response, true));
	}

	/**
	 * Creates an order in Klarna from the recurring token saved.
	 *
	 * @param string $renewal_total The total price for the order.
	 * @param object $renewal_order The WooCommerce order for the renewal.
	 */
	public function check_subscription_status( $order_id, $subscription_bulk_id ) {
		$order = wc_get_order( $order_id );
		// $payment_id       = $order->get_transaction_id();
		$recurring_token = get_post_meta( $order_id, '_dibs_recurring_token', true );

		$recurring_orders = new DIBS_Request_Get_Subscription_Bulk_Id( $subscription_bulk_id );
		$recurring_orders = $recurring_orders->request();
		$payment_id       = null;
		foreach ( $recurring_orders->page as $recurring_order ) {
			if ( $recurring_order->subscriptionId == $recurring_token ) {
				$payment_id = $recurring_order->paymentId;
				break;
			}
		}

		if ( ! empty( $payment_id ) ) {

			if ( 'Succeeded' == $recurring_order->status ) {
				WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
				$order->add_order_note( sprintf( __( 'Subscription payment made with DIBS. DIBS order id: %s', 'dibs-easy-for-woocommerce' ), $payment_id ) );
				$order->payment_complete( $payment_id );
			} else {
				$order->add_order_note( sprintf( __( 'Payment status not correct for subscription. Status: %s', 'dibs-easy-for-woocommerce' ), $recurring_order->status ) );
				WC_Subscriptions_Manager::process_subscription_payment_failure_on_order( $order );
			}
		} else {
			$order->add_order_note( sprintf( __( 'Subscription payment failed with DIBS during scheduled request. No paymentId found in response', 'dibs-easy-for-woocommerce' ), 'fel' ) );
		}
	}

	public function show_recurring_token( $order ) {
		if ( 'shop_subscription' === $order->get_type() && get_post_meta( $order->get_id(), '_dibs_recurring_token' ) ) {
			?>
			<div class="order_data_column" style="clear:both; float:none; width:100%;">
				<div class="address">
					<?php
						echo '<p><strong>' . __( 'DIBS recurring token' ) . ':</strong>' . get_post_meta( $order->id, '_dibs_recurring_token', true ) . '</p>';
					?>
				</div>
				<div class="edit_address">
					<?php
						woocommerce_wp_text_input(
							array(
								'id'            => '_dibs_recurring_token',
								'label'         => __( 'DIBS recurring token' ),
								'wrapper_class' => '_billing_company_field',
							)
						);
					?>
				</div>
			</div>
		<?php
		}
	}

	public function save_dibs_recurring_token_update( $post_id, $post ) {
		$order = wc_get_order( $post_id );
		if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $order ) && get_post_meta( $post_id, '_dibs_recurring_token' ) ) {
			update_post_meta( $post_id, '_dibs_recurring_token', wc_clean( $_POST['_dibs_recurring_token'] ) );
		}

	}
}
new DIBS_Subscriptions();
