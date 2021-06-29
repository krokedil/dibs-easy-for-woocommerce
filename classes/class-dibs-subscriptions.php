<?php
/**
 * Handles subscription payments with Nets.
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Handles subscription payments with Nets.
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

		add_action( 'init', array( $this, 'dibs_payment_method_changed' ) );

		add_filter( 'woocommerce_order_needs_payment', array( $this, 'maybe_change_needs_payment' ), 999, 3 );

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
				'endDate'  => gmdate( 'Y-m-d\TH:i', strtotime( '+5 year' ) ),
				'interval' => 0,
			);

			$complete_payment_button_text = ( isset( $dibs_settings['complete_payment_button_text'] ) ) ? $dibs_settings['complete_payment_button_text'] : 'subscribe';
			$request_args['appearance']['textOptions']['completePaymentButtonText'] = $complete_payment_button_text;
		}

		// Checks if this is a DIBS subscription payment method change.
		$key                   = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_STRING );
		$change_payment_method = filter_input( INPUT_GET, 'change_payment_method', FILTER_SANITIZE_STRING );
		if ( ! empty( $key ) && ! empty( $change_payment_method ) ) {
			$order_id = wc_get_order_id_by_order_key( sanitize_key( $key ) );
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
							'endDate'  => gmdate( 'Y-m-d\TH:i', strtotime( '+5 year' ) ),
							'interval' => 0,
						);
					}
				}
			}
		}

		return $request_args;
	}

	/**
	 * Handles subscription payment method change.
	 */
	public function dibs_payment_method_changed() {
		$dibs_action = filter_input( INPUT_GET, 'dibs-action', FILTER_SANITIZE_STRING );
		$order_id    = filter_input( INPUT_GET, 'wc-subscription-id', FILTER_SANITIZE_STRING );
		$payment_id  = filter_input( INPUT_GET, 'paymentid', FILTER_SANITIZE_STRING );

		if ( ! empty( $dibs_action ) && 'subs-payment-changed' === $dibs_action && ! empty( $order_id ) && ! empty( $payment_id ) ) {

			$request = new DIBS_Requests_Get_DIBS_Order( $payment_id, $order_id );
			$request = $request->request();

			if ( isset( $request->payment->subscription->id ) ) {
				$old_subscription_id = get_post_meta( $order_id, '_dibs_recurring_token', true );
				$new_subscription_id = $request->payment->subscription->id;

				if ( $old_subscription_id !== $new_subscription_id ) {
					update_post_meta( $order_id, '_dibs_recurring_token', $request->payment->subscription->id );
					update_post_meta( $order_id, 'dibs_payment_method', $request->payment->paymentDetails->paymentMethod );
					update_post_meta( $order_id, 'dibs_customer_card', $request->payment->paymentDetails->cardDetails->maskedPan );
				}
			} else {
				wc_clear_notices(); // Customer did not finalize the payment method change.
			}
		}
	}

	/**
	 * Returns the SKU used in Nets for the prodct.
	 *
	 * @param object $product WooCommerce product.
	 * @param string $product_id WooCommerce product id.
	 *
	 * @return string
	 */
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
	 * @param string $order_id WooCommerce order id.
	 * @param object $dibs_order Nets order.
	 *
	 * @return object
	 */
	public function set_recurring_token_for_order( $order_id, $dibs_order ) {
		$wc_order = wc_get_order( $order_id );
		if ( isset( $dibs_order->payment->subscription->id ) ) {
			update_post_meta( $order_id, '_dibs_recurring_token', $dibs_order->payment->subscription->id );

			$dibs_subscription = new DIBS_Requests_Get_Subscription( $dibs_order->payment->subscription->id, $order_id );
			$request           = $dibs_subscription->request();
			if ( 'CARD' == $request->paymentDetails->paymentType ) { // phpcs:ignore
				update_post_meta( $order_id, 'dibs_payment_type', $request->paymentDetails->paymentType ); // phpcs:ignore
				update_post_meta( $order_id, 'dibs_customer_card', $request->paymentDetails->cardDetails->maskedPan ); // phpcs:ignore
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

					if ( ! is_wp_error( $response ) && ! empty( $response->subscriptionId ) ) { // phpcs:ignore
						// All good, save the subscription ID as _dibs_recurring_token in the renewal order and in the subscription.
						$recurring_token = $response->subscriptionId; // phpcs:ignore
						update_post_meta( $order_id, '_dibs_recurring_token', $recurring_token );

						foreach ( $subcriptions as $subcription ) {
							update_post_meta( $subcription->get_id(), '_dibs_recurring_token', $recurring_token );
							$subcription->add_order_note( sprintf( __( 'Saved _dibs_recurring_token in subscription by externalreference request to Nets. Recurring token: %s', 'dibs-easy-for-woocommerce' ), $response->subscriptionId ) ); // phpcs:ignore
						}
						if ( 'CARD' === $response->paymentDetails->paymentType ) { // phpcs:ignore
							// Save card data in renewal order.
							update_post_meta( $order_id, 'dibs_payment_type', $response->paymentDetails->paymentType ); // phpcs:ignore
							update_post_meta( $order_id, 'dibs_customer_card', $response->paymentDetails->cardDetails->maskedPan ); // phpcs:ignore
						}
					} else {
						/* Translators: Request response. */
						$renewal_order->add_order_note( sprintf( __( 'Error during DIBS_Request_Get_Subscription_By_External_Reference: %s', 'dibs-easy-for-woocommerce' ), wp_json_encode( $response ) ) );
					}
				}
			}
		}

		$create_order_response = new DIBS_Request_Charge_Subscription( $order_id, $recurring_token );
		$create_order_response = $create_order_response->request();

		if ( ! is_wp_error( $create_order_response ) && ! empty( $create_order_response->paymentId ) ) { // phpcs:ignore

			// All good. Update the renewal order with an order note and run payment_complete on all subscriptions.
			update_post_meta( $order_id, '_dibs_date_paid', gmdate( 'Y-m-d H:i:s' ) );
			update_post_meta( $order_id, '_dibs_charge_id', $create_order_response->chargeId ); // phpcs:ignore
			/* Translators: Nets Payment ID & Charge ID. */
			$renewal_order->add_order_note( sprintf( __( 'Subscription payment made with Nets. Payment ID: %1$s. Charge ID %2$s.', 'dibs-easy-for-woocommerce' ), $create_order_response->paymentId, $create_order_response->chargeId ) ); // phpcs:ignore

			foreach ( $subscriptions as $subscription ) {
				$subscription->payment_complete( $create_order_response->paymentId ); // phpcs:ignore
			}
		} else {
			/* Translators: Request response from Nets. */
			$renewal_order->add_order_note( sprintf( __( 'Subscription payment failed with Nets. Error message: %1$s.', 'dibs-easy-for-woocommerce' ), wp_json_encode( $create_order_response ) ) );
			foreach ( $subscriptions as $subscription ) {
				$subscription->payment_failed();
			}
		}
	}

	/**
	 * Show recurring token in Subscription page in WP admin.
	 *
	 * @param string $order WooCommerce order.
	 */
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

	/**
	 * Save recurring token to order.
	 *
	 * @param string $post_id WC order id.
	 * @param object $post WordPress post.
	 */
	public function save_dibs_recurring_token_update( $post_id, $post ) {
		$order = wc_get_order( $post_id );
		if ( class_exists( 'WC_Subscriptions' ) && wcs_order_contains_subscription( $order ) && get_post_meta( $post_id, '_dibs_recurring_token' ) ) {
			$dibs_recurring_token = filter_input( INPUT_POST, '_dibs_recurring_token', FILTER_SANITIZE_STRING );
			if ( ! empty( $dibs_recurring_token ) ) {
				update_post_meta( $post_id, '_dibs_recurring_token', $dibs_recurring_token );
			}
		}

	}

	/**
	 * Maybe change the needs payment for a WooCommerce order.
	 * Used to trigger process_payment for subscrition parent orders with a recurring coupon that results in a 0 value order.
	 *
	 * @param bool     $wc_result The result WooCommerce had.
	 * @param WC_Order $order The WooCommerce order.
	 * @param array    $valid_order_statuses The valid order statuses.
	 * @return bool
	 */
	public function maybe_change_needs_payment( $wc_result, $order, $valid_order_statuses ) {

		// Only change for Nets Easy orders.
		if ( 'dibs_easy' !== $order->get_payment_method() ) {
			return $wc_result;
		}

		// Only change for subscription orders.
		if ( ! $this->has_subscription( $order->get_id() ) ) {
			return $wc_result;
		}

		// Only change in checkout.
		if ( ! is_checkout() ) {
			return $wc_result;
		}

		return true;
	}

	/**
	 * Is $order_id a subscription?
	 *
	 * @param  int $order_id WooCommerce order id.
	 * @return boolean
	 */
	public function has_subscription( $order_id ) {
		return ( function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_is_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) ) );
	}
}
new DIBS_Subscriptions();
