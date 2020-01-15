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

		// Charge renewal payment.
		add_action( 'woocommerce_scheduled_subscription_payment_dibs_easy', array( $this, 'trigger_scheduled_payment' ), 10, 2 );

		add_action( 'wc_dibs_easy_check_subscription_status', array( $this, 'check_subscription_status' ), 10, 2 );

		add_action( 'init', array( $this, 'dibs_payment_method_changed' ) );

	}

	/**
	 * Marks the order as a recurring order for Klarna
	 *
	 * @param array $request_args The Klarna request arguments.
	 * @return array
	 */
	public function maybe_add_subscription( $request_args ) {
		// Check if we have a subscription product. If yes set recurring field.
		if ( class_exists( 'WC_Subscriptions_Cart' ) && ( WC_Subscriptions_Cart::cart_contains_subscription() || wcs_cart_contains_renewal() ) ) {
			$request_args['subscription'] = array(
				'endDate'  => date( 'Y-m-d\TH:i', strtotime( '+150 year' ) ),
				'interval' => 0,
			);
		}

		// Checks if this is a DIBS subscription payment method change.
		if ( isset( $_GET['key'] ) && isset( $_GET['change_payment_method'] ) ) {
			$order_id = wc_get_order_id_by_order_key( sanitize_key( $_GET['key'] ) );
			if ( $order_id ) {
				$wc_order = wc_get_order( $order_id );
				if ( is_object( $wc_order ) && function_exists( 'wcs_order_contains_subscription' ) && function_exists( 'wcs_is_subscription' ) ) {
					if ( wcs_order_contains_subscription( $wc_order, array( 'parent', 'renewal', 'resubscribe', 'switch' ) ) || wcs_is_subscription( $wc_order ) ) {

						// Modify order lines.
						$order_items = array();
						foreach ( $wc_order->get_items() as $item ) {
							$product = $item->get_product();
							if ( $item['variation_id'] ) {
								$product_id = $item['variation_id'];
							} else {
								$product_id = $item['product_id'];
							}
							$order_items[] = array(
								'reference'        => self::get_sku( $product, $product_id ),
								'name'             => $item->get_name(),
								'quantity'         => $item->get_quantity(),
								'unit'             => __( 'pcs', 'dibs-easy-for-woocommerce' ),
								'unitPrice'        => 0,
								'taxRate'          => 0,
								'taxAmount'        => 0,
								'grossTotalAmount' => 0,
								'netTotalAmount'   => 0,
							);
						}

						$order_lines           = array(
							'items'     => $order_items,
							'amount'    => 0,
							'currency'  => $wc_order->get_currency(),
							'reference' => $wc_order->get_order_number(),
						);
						$request_args['order'] = $order_lines;

						// Modify return url.
						$request_args['checkout']['returnUrl'] = add_query_arg(
							array(
								'dibs-action'        => 'subs-payment-changed',
								'wc-subscription-id' => $order_id,
							),
							$wc_order->get_view_order_url()
						);

						unset( $request_args['notifications'] );

						$request_args['subscription'] = array(
							'endDate'  => date( 'Y-m-d\TH:i', strtotime( '+150 year' ) ),
							'interval' => 0,
						);
					}
				}
			}
		}

		return $request_args;
	}

	public function dibs_payment_method_changed() {
		if ( isset( $_GET['dibs-action'] ) && 'subs-payment-changed' === $_GET['dibs-action'] && isset( $_GET['wc-subscription-id'] ) && isset( $_GET['paymentid'] ) ) {
			$order_id   = $_GET['wc-subscription-id'];
			$payment_id = get_post_meta( $order_id, '_dibs_payment_id', true ); // use paymentid param value instead?

			$request = new DIBS_Requests_Get_DIBS_Order( $payment_id, $order_id );
			$request = $request->request();

			if ( isset( $request->payment->subscription->id ) ) {
				update_post_meta( $order_id, '_dibs_recurring_token', $request->payment->subscription->id );
				update_post_meta( $order_id, 'dibs_payment_method', $request->payment->paymentDetails->paymentMethod );
				update_post_meta( $order_id, 'dibs_customer_card', $request->payment->paymentDetails->cardDetails->maskedPan );
			}
		}
	}

	public static function get_sku( $product, $product_id ) {
		if ( get_post_meta( $product_id, '_sku', true ) !== '' ) {
			$part_number = $product->get_sku();
		} else {
			$part_number = $product->get_id();
		}
		return substr( $part_number, 0, 32 );
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

			$dibs_subscription = new DIBS_Requests_Get_Subscription( $dibs_order->payment->subscription->id, $order_id );
			$request           = $dibs_subscription->request();
			if ( 'CARD' == $request->paymentDetails->paymentType ) {
				update_post_meta( $order_id, 'dibs_payment_type', $request->paymentDetails->paymentType );
				update_post_meta( $order_id, 'dibs_customer_card', $request->paymentDetails->cardDetails->maskedPan );
			}

			// This function is run after WCS has created the subscription order.
			// Let's add the _dibs_recurring_token to the subscription as well.
			if ( class_exists( 'WC_Subscriptions' ) && ( wcs_order_contains_subscription( $wc_order, array( 'parent', 'renewal', 'resubscribe', 'switch' ) ) || wcs_is_subscription( $wc_order ) ) ) {
				$subcriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'any' ) );
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
		$order_id      = $renewal_order->get_id();
		$subscriptions = wcs_get_subscriptions_for_renewal_order( $order_id );

		// Get recurring token.
		$recurring_token = get_post_meta( $order_id, '_dibs_recurring_token', true );

		// If _dibs_recurring_token is missing.
		if ( empty( $recurring_token ) ) {
			// Try getting it from parent order.
			$parent_order_recurring_token = get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_dibs_recurring_token', true );
			if ( ! empty( $parent_order_recurring_token ) ) {
				$recurring_token = $parent_order_recurring_token;
				update_post_meta( $order_id, '_dibs_recurring_token', $recurring_token );
			} else {
				// Try to get recurring token from old D2 _dibs_ticket.
				$dibs_ticket = get_post_meta( $order_id, '_dibs_ticket', true );
				if ( empty( $dibs_ticket ) ) {
					// Try to get recurring token from old D2 _dibs_ticket parent order.
					$dibs_ticket = get_post_meta( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ), '_dibs_ticket', true );
				}
				if ( ! empty( $dibs_ticket ) ) {
					// We got a _dibs_ticket - try to getting the subscription via the externalreference request.
					$subscription_request = new DIBS_Request_Get_Subscription_By_External_Reference( $dibs_ticket, $order_id );
					$response             = $subscription_request->request();

					if ( ! is_wp_error( $response ) && ! empty( $response->subscriptionId ) ) {
						// All good, save the subscription ID as _dibs_recurring_token in the renewal order and in the subscription.
						$recurring_token = $response->subscriptionId;
						update_post_meta( $order_id, '_dibs_recurring_token', $recurring_token );
						// $subcriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'any' ) );
						foreach ( $subcriptions as $subcription ) {
							update_post_meta( $subcription->get_id(), '_dibs_recurring_token', $recurring_token );
							$subcription->add_order_note( sprintf( __( 'Saved _dibs_recurring_token in subscription by externalreference request to Nets. Recurring token: %s', 'dibs-easy-for-woocommerce' ), $response->subscriptionId ) );
						}
						if ( 'CARD' === $response->paymentDetails->paymentType ) {
							// Save card data in renewal order.
							update_post_meta( $order_id, 'dibs_payment_type', $response->paymentDetails->paymentType );
							update_post_meta( $order_id, 'dibs_customer_card', $response->paymentDetails->cardDetails->maskedPan );
						}
					} else {
						$renewal_order->add_order_note( sprintf( __( 'Error during DIBS_Request_Get_Subscription_By_External_Reference: %s', 'dibs-easy-for-woocommerce' ), wp_json_encode( $response ) ) );
					}
				}
			}
		}

		$create_order_response = new DIBS_Request_Charge_Subscription( $order_id );
		$create_order_response = $create_order_response->request();

		if ( ! is_wp_error( $create_order_response ) && ! empty( $create_order_response->bulkId ) ) {
			// We got a bulkId in response. Save it in the renewal order and make a new request to DIBS to get the status and ID of the transaction
			update_post_meta( $order_id, '_dibs_recurring_bulk_id', $create_order_response->bulkId );

			$recurring_orders = new DIBS_Request_Get_Subscription_Bulk_Id( $create_order_response->bulkId, $order_id );
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
					// All good. Update the renewal order with an order note and run payment_complete on all subscriptions.
					update_post_meta( $order_id, '_dibs_date_paid', date( 'Y-m-d H:i:s' ) );
					$renewal_order->add_order_note( sprintf( __( 'Subscription payment made with Nets. Nets order id: %s', 'dibs-easy-for-woocommerce' ), $payment_id ) );

					foreach ( $subscriptions as $subscription ) {
						$subscription->payment_complete( $payment_id );
					}
				} else {
					// Payment status not available yet. Schedule new check in 1 minute.
					$renewal_order->add_order_note( sprintf( __( 'Payment status not available: Scheduling payment status check to be triggered in 1 minute.', 'dibs-easy-for-woocommerce' ), $payment_id ) );
					wp_schedule_single_event( time() + 60, 'wc_dibs_easy_check_subscription_status', array( $order_id, $create_order_response->bulkId ) );
				}
			} else {
				// Something is wrong. Run payment_failed on all subscriptions.
				$renewal_order->add_order_note( sprintf( __( 'Subscription payment failed with Nets. Error code: %1$s. Message: %2$s', 'dibs-easy-for-woocommerce' ), 'fel', $create_order_response->bulkId ) );
				foreach ( $subscriptions as $subscription ) {
					$subscription->payment_failed();
				}
			}
		} else {
			$renewal_order->add_order_note( sprintf( __( 'Subscription payment failed with Nets. Error message: %1$s.', 'dibs-easy-for-woocommerce' ), wp_json_encode( $create_order_response ) ) );
			foreach ( $subscriptions as $subscription ) {
				$subscription->payment_failed();
			}
		}
	}

	/**
	 * Creates an order in Klarna from the recurring token saved.
	 *
	 * @param string $renewal_total The total price for the order.
	 * @param object $renewal_order The WooCommerce order for the renewal.
	 */
	public function check_subscription_status( $order_id, $subscription_bulk_id ) {
		$order         = wc_get_order( $order_id );
		$subscriptions = wcs_get_subscriptions_for_renewal_order( $order_id );
		// $payment_id       = $order->get_transaction_id();
		$recurring_token = get_post_meta( $order_id, '_dibs_recurring_token', true );

		$recurring_orders = new DIBS_Request_Get_Subscription_Bulk_Id( $subscription_bulk_id, $order_id );
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

				// All good. Update the renewal order with an order note and run payment_complete on all subscriptions.
				update_post_meta( $order_id, '_dibs_date_paid', date( 'Y-m-d H:i:s' ) );
				$order->add_order_note( sprintf( __( 'Subscription payment made with Nets. Nets order id: %s', 'dibs-easy-for-woocommerce' ), $payment_id ) );

				foreach ( $subscriptions as $subscription ) {
					$subscription->payment_complete( $payment_id );
				}
			} else {
				$order->add_order_note( sprintf( __( 'Payment status not correct for subscription. Status: %1$s. Message: %2$s', 'dibs-easy-for-woocommerce' ), $recurring_order->status, $recurring_order->message ) );
				foreach ( $subscriptions as $subscription ) {
					$subscription->payment_failed();
				}
			}
		} else {
			$order->add_order_note( sprintf( __( 'Subscription payment failed with Nets during scheduled request. No paymentId found in response', 'dibs-easy-for-woocommerce' ), 'fel' ) );
			foreach ( $subscriptions as $subscription ) {
				$subscription->payment_failed();
			}
		}
	}

	public function show_recurring_token( $order ) {
		if ( 'shop_subscription' === $order->get_type() && get_post_meta( $order->get_id(), '_dibs_recurring_token' ) ) {
			?>
			<div class="order_data_column" style="clear:both; float:none; width:100%;">
				<div class="address">
				<?php
					echo '<p><strong>' . esc_html( __( 'Nets recurring token' ) ) . ':</strong>' . esc_html( get_post_meta( $order->get_id(), '_dibs_recurring_token', true ) ) . '</p>';
				?>
				</div>
				<div class="edit_address">
				<?php
					woocommerce_wp_text_input(
						array(
							'id'            => '_dibs_recurring_token',
							'label'         => __( 'Nets recurring token' ),
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
