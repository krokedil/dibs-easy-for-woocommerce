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
 */
class Nets_Easy_Subscriptions {

	/**
	 * Reference to dibs_settings.
	 *
	 * @var $array
	 */
	public $dibs_settings;

	/**
	 * The subscription type
	 *
	 * @var string
	 */
	public $subscription_type;

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

		$this->dibs_settings     = get_option( 'woocommerce_dibs_easy_settings' );
		$subscription_type       = $this->dibs_settings['subscription_type'] ?? 'scheduled_subscription';
		$this->subscription_type = apply_filters( 'nets_easy_subscription_type', $subscription_type );
	}

	/**
	 * Marks the order as a recurring order for Nexi Checkout
	 *
	 * @param array $request_args The Nexi Checkout request arguments.
	 * @return array
	 */
	public function maybe_add_subscription( $request_args ) {
		// Check if we have a subscription product. If yes set recurring fi eld.
		if ( class_exists( 'WC_Subscriptions_Cart' ) && ( WC_Subscriptions_Cart::cart_contains_subscription() || wcs_cart_contains_renewal() ) ) {

			// Unscheduled or scheduled subscription?
			if ( 'unscheduled_subscription' === $this->subscription_type ) {
				$request_args['unscheduledSubscription'] = array(
					'create' => true,
				);
			} else {
				$request_args['subscription'] = array(
					'endDate'  => gmdate( 'Y-m-d\TH:i', strtotime( '+5 year' ) ),
					'interval' => 0,
				);
			}

			$dibs_settings                = get_option( 'woocommerce_dibs_easy_settings' );
			$complete_payment_button_text = $dibs_settings['complete_payment_button_text'] ?? 'subscribe';
			$request_args['checkout']['appearance']['textOptions']['completePaymentButtonText'] = $complete_payment_button_text;
		}

		// Checks if this is a DIBS subscription payment method change.
		$key                   = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$change_payment_method = filter_input( INPUT_GET, 'change_payment_method', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
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
						$request_args['checkout']['returnUrl'] = esc_url_raw(
							add_query_arg(
								array(
									'dibs-action'        => 'subs-payment-changed',
									'wc-subscription-id' => $order_id,
								),
								$wc_order->get_view_order_url()
							)
						);

						unset( $request_args['notifications'] );

						// Unscheduled or scheduled subscription?
						if ( 'unscheduled_subscription' === $this->subscription_type ) {
							$request_args['unscheduledSubscription'] = array(
								'create' => true,
							);
						} else {
							$request_args['subscription'] = array(
								'endDate'  => gmdate( 'Y-m-d\TH:i', strtotime( '+5 year' ) ),
								'interval' => 0,
							);
						}
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
		$dibs_action = filter_input( INPUT_GET, 'dibs-action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$order_id    = filter_input( INPUT_GET, 'wc-subscription-id', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$payment_id  = filter_input( INPUT_GET, 'paymentid', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$order       = wc_get_order( $order_id );

		if ( ! empty( $dibs_action ) && 'subs-payment-changed' === $dibs_action && ! empty( $order_id ) && ! empty( $payment_id ) ) {
			$response = Nets_Easy()->api->get_nets_easy_order( $payment_id );
			if ( ! is_wp_error( $response ) ) {
				$this->set_recurring_token_for_order( $order_id, $response );

				$order->update_meta_data( 'dibs_payment_type', $response['payment']['paymentDetails']['paymentType'] );
				$order->update_meta_data( 'dibs_payment_method', $response['payment']['paymentDetails']['paymentMethod'] );
				$order->save();

				if ( 'CARD' === $response['payment']['paymentDetails']['paymentType'] ) {
					$order->update_meta_data( 'dibs_customer_card', $response['payment']['paymentDetails']['cardDetails']['maskedPan'] );
					$order->save();
				}
			} else {
				wc_clear_notices(); // Customer did not finalize the payment method change.
			}
		}
	}

	/**
	 * Returns the SKU used in Nets for the product.
	 *
	 * @param object $product WooCommerce product.
	 * @param string $product_id WooCommerce product id.
	 *
	 * @return string
	 */
	public static function get_sku( $product, $product_id ) {
		$part_number = $product->get_sku();
		if ( empty( $part_number ) ) {
			$part_number = $product->get_id();
		}
		return substr( $part_number, 0, 32 );
	}


	/**
	 * Sets the recurring token for the subscription order
	 *
	 * @param string $order_id WooCommerce order id.
	 * @param array  $dibs_order Nets order.
	 *
	 * @return array|false On success the same $dibs_order is returned, otherwise FALSE if something goes wrong.
	 */
	public function set_recurring_token_for_order( $order_id, $dibs_order ) {
		$wc_order = wc_get_order( $order_id );
		if ( isset( $dibs_order['payment']['subscription']['id'] ) || isset( $dibs_order['payment']['unscheduledSubscription']['unscheduledSubscriptionId'] ) ) {

			if ( isset( $dibs_order['payment']['subscription']['id'] ) ) {
				$subscription_id   = $dibs_order['payment']['subscription']['id'];
				$subscription_type = 'scheduled_subscription';
			} else {
				$subscription_id   = $dibs_order['payment']['unscheduledSubscription']['unscheduledSubscriptionId'];
				$subscription_type = 'unscheduled_subscription';
			}
			// Translators: Nets subscription recurring token.
			$wc_order->add_order_note( sprintf( __( 'Nexi Checkout subscription ID/recurring token %s saved.', 'dibs-easy-for-woocommerce' ), $subscription_id ) );
			$wc_order->update_meta_data( '_dibs_recurring_token', $subscription_id );
			$wc_order->update_meta_data( '_dibs_subscription_type', $subscription_type );
			$wc_order->save();

			// This function is run after WCS has created the subscription order.
			// Let's add the _dibs_recurring_token to the subscription as well.
			if ( class_exists( 'WC_Subscriptions' ) && ( wcs_order_contains_subscription(
				$wc_order,
				array(
					'parent',
					'renewal',
					'resubscribe',
					'switch',
				)
			) || wcs_is_subscription( $wc_order ) ) ) {
				$subscriptions = wcs_get_subscriptions_for_order( $order_id, array( 'order_type' => 'any' ) );
				foreach ( $subscriptions as $subscription ) {
					// Translators: Nets subscription recurring token.
					$subscription->add_order_note( sprintf( __( 'Nexi Checkout subscription ID/recurring token %s saved.', 'dibs-easy-for-woocommerce' ), $subscription_id ) );
					$subscription->update_meta_data( '_dibs_recurring_token', $subscription_id );
					$subscription->update_meta_data( '_dibs_subscription_type', $subscription_type );

					$subscription->update_meta_data( 'dibs_payment_type', $dibs_order['payment']['paymentDetails']['paymentType'] );
					$subscription->update_meta_data( 'dibs_payment_method', $dibs_order['payment']['paymentDetails']['paymentMethod'] );
					if ( 'CARD' === $dibs_order['payment']['paymentDetails']['paymentType'] ) {
						$subscription->update_meta_data( 'dibs_customer_card', $dibs_order['payment']['paymentDetails']['cardDetails']['maskedPan'] );
					}
					$subscription->save();
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
		$order         = wc_get_order( $order_id );

		// Get recurring token.
		$recurring_token = $renewal_order->get_meta( '_dibs_recurring_token' );

		// Subscription type.
		$subscription_type = ! empty( $renewal_order->get_meta( '_dibs_subscription_type' ) ) ? $renewal_order->get_meta( '_dibs_subscription_type' ) : $this->subscription_type;

		// If _dibs_recurring_token is missing.
		if ( empty( $recurring_token ) ) {
			// Try getting it from parent order.
			$parent_order_recurring_token = wc_get_order( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ) )->get_meta( '_dibs_recurring_token' );
			if ( ! empty( $parent_order_recurring_token ) ) {
				$recurring_token = $parent_order_recurring_token;
				$order->update_meta_data( '_dibs_recurring_token', $recurring_token );
				$order->save();
			} else {
				// Try to get recurring token from old D2 _dibs_ticket.
				$dibs_ticket = $renewal_order->get_meta( '_dibs_ticket' );

				if ( empty( $dibs_ticket ) ) {
					// Try to get recurring token from old D2 _dibs_ticket parent order.
					$dibs_ticket = wc_get_order( WC_Subscriptions_Renewal_Order::get_parent_order_id( $order_id ) )->get_meta( '_dibs_ticket' );
				}
				if ( ! empty( $dibs_ticket ) ) {
					// We got a _dibs_ticket - try to getting the subscription via the externalreference request.
					if ( 'unscheduled_subscription' === $subscription_type ) {
						$recurring_token = $this->get_recurring_token_from_unscheduled_subscription_external_reference( $dibs_ticket, $order_id, $subscriptions, $renewal_order );
					} else {
						$recurring_token = $this->get_recurring_token_from_scheduled_subscription_external_reference( $dibs_ticket, $order_id, $subscriptions, $renewal_order );
					}
				}
			}
		}
		// Unscheduled or scheduled subscription charge?
		if ( 'unscheduled_subscription' === $subscription_type ) {
			$response = Nets_Easy()->api->charge_nets_easy_unscheduled_subscription( $order_id, $recurring_token );
		} else {
			$response = Nets_Easy()->api->charge_nets_easy_scheduled_subscription( $order_id, $recurring_token );
		}

		if ( ! is_wp_error( $response ) && ! empty( $response['paymentId'] ) ) { // phpcs:ignore

			// All good. Update the renewal order with an order note and run payment_complete on all subscriptions.
			$order->update_meta_data( '_dibs_date_paid', gmdate( 'Y-m-d H:i:s' ) );
			$order->update_meta_data( '_dibs_charge_id', $response['chargeId'] );
			$order->update_meta_data( '_dibs_subscription_type', $subscription_type );
			$order->save();
			/* Translators: Nets Payment ID & Charge ID. */
			$renewal_order->add_order_note( sprintf( __( 'Subscription payment made with Nets. Payment ID: %1$s. Charge ID %2$s.', 'dibs-easy-for-woocommerce' ), $response['paymentId'], $response['chargeId'] ) ); // phpcs:ignore

			foreach ( $subscriptions as $subscription ) {
				$subscription->payment_complete( $response['paymentId'] ); // phpcs:ignore
				$subscription->update_meta_data( '_dibs_subscription_type', $subscription_type );
				$subscription->save();
			}
		} else {
			/* Translators: Request response from Nets. */
			$renewal_order->add_order_note( sprintf( __( 'Subscription payment failed with Nets. Error message: %s.', 'dibs-easy-for-woocommerce' ), wp_json_encode( $response->get_error_message() ) ) );// TODO check the type.
			foreach ( $subscriptions as $subscription ) {
				$subscription->payment_failed();
			}
		}
	}

	/**
	 * Try to get recurring token by external reference from scheduled subscription.
	 *
	 * @param string  $dibs_ticket The recurring ticket from the old DIBS subscription platform.
	 * @param string  $order_id The WooCommerce order ID.
	 * @param objects $subscriptions WooCommerce Subscriptions tied to the renewal order.
	 * @param object  $renewal_order The WooCommerce order for the renewal.
	 *
	 * @return string The recurring token.
	 */
	public function get_recurring_token_from_scheduled_subscription_external_reference( $dibs_ticket, $order_id, $subscriptions, $renewal_order ) {
		$recurring_token = '';
		$response        = Nets_Easy()->api->get_nets_easy_subscription_by_external_reference( $dibs_ticket, $order_id );
		$order           = wc_get_order( $order_id );

		if ( ! is_wp_error( $response ) && isset( $response['subscriptionId'] ) ) { // phpcs:ignore
			// All good, save the subscription ID as _dibs_recurring_token in the renewal order and in the subscription.
			$recurring_token = $response['subscriptionId']; // phpcs:ignore
			$order->update_meta_data( '_dibs_recurring_token', $recurring_token );
			$order->save();

			foreach ( $subscriptions as $subscription ) {
				$subscription->update_meta_data( '_dibs_recurring_token', $recurring_token );
				$subscription->save();
				// Translators: Nets recurring token.
				$subscription->add_order_note( sprintf( __( 'Saved _dibs_recurring_token in subscription by externalreference request to Nets. Recurring token: %s', 'dibs-easy-for-woocommerce' ), $response['subscriptionId'] ) );
			}
			if ( 'CARD' === $response['paymentDetails']['paymentType'] ) { // phpcs:ignore
				// Save card data in renewal order.
				$order->update_meta_data( 'dibs_payment_type', $response['paymentDetails']['paymentType'] );
				$order->update_meta_data( 'dibs_customer_card', $response['paymentDetails']['cardDetails']['maskedPan'] );
				$order->save();
			}
		} else {
			/* Translators: Request response. */
			$renewal_order->add_order_note( sprintf( __( 'Error during Nets_Easy_Request_Get_Subscription_By_External_Reference: %s', 'dibs-easy-for-woocommerce' ), wp_json_encode( $response ) ) );
		}

		return $recurring_token;
	}

	/**
	 * Try to get recurring token by external reference from unscheduled subscription.
	 *
	 * @param string  $dibs_ticket The recurring ticket from the old DIBS subscription platform.
	 * @param string  $order_id The WooCommerce order ID.
	 * @param objects $subscriptions WooCommerce Subscriptions tied to the renewal order.
	 * @param object  $renewal_order The WooCommerce order for the renewal.
	 *
	 * @return string The recurring token.
	 */
	public function get_recurring_token_from_unscheduled_subscription_external_reference( $dibs_ticket, $order_id, $subscriptions, $renewal_order ) {
		$recurring_token = '';
		$response        = Nets_Easy()->api->get_nets_easy_unscheduled_subscription_by_external_reference( $dibs_ticket, $order_id );
		$order           = wc_get_order( $order_id );

		if ( ! is_wp_error( $response ) && isset( $response['unscheduledSubscriptionId'] ) ) { // phpcs:ignore
			// All good, save the subscription ID as _dibs_recurring_token in the renewal order and in the subscription.
			$recurring_token = $response['unscheduledSubscriptionId']; // phpcs:ignore
			$order->update_meta_data( '_dibs_recurring_token', $recurring_token );
			$order->save();

			foreach ( $subscriptions as $subscription ) {
				$subscription->update_meta_data( '_dibs_recurring_token', $recurring_token );
				$subscription->save();
				// Translators: Recurring token & subscription type.
				$subscription->add_order_note( sprintf( __( 'Saved _dibs_recurring_token in subscription by externalreference request to Nets. Recurring token:%1$s. Subscription type: %2$s.', 'dibs-easy-for-woocommerce' ), $recurring_token, 'Unscheduled' ) ); // phpcs:ignore
			}
			if ( 'CARD' === $response['paymentDetails']['paymentType'] ) { // phpcs:ignore
				// Save card data in renewal order.
				$order->update_meta_data( 'dibs_payment_type', $response['paymentDetails']['paymentType'] );
				$order->update_meta_data( 'dibs_customer_card', $response['paymentDetails']['cardDetails']['maskedPan'] );
				$order->save();
			}
		} else {
			/* Translators: Request response. */
			$renewal_order->add_order_note( sprintf( __( 'Error during Nets_Easy_Request_Get_Unscheduled_Subscription_By_External_Reference: %s', 'dibs-easy-for-woocommerce' ), wp_json_encode( $response ) ) );
		}

		return $recurring_token;
	}

	/**
	 * Show recurring token in Subscription page in WP admin.
	 *
	 * @param WC_Order $order WooCommerce order.
	 */
	public function show_recurring_token( $order ) {
		if ( 'shop_subscription' === $order->get_type() && $order->get_meta( '_dibs_recurring_token' ) ) {
			?>
			<div class="order_data_column" style="clear:both; float:none; width:100%;">
				<div class="address">
				<?php
					echo '<p><strong>' . esc_html( __( 'Nets recurring token' ) ) . ':</strong>' . esc_html( $order->get_meta( '_dibs_recurring_token' ) ) . '</p>';
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
		if ( 'shop_subscription' === $order->get_type() && $order->get_meta( '_dibs_recurring_token' ) ) {
				$dibs_recurring_token = filter_input( INPUT_POST, '_dibs_recurring_token', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( ! empty( $dibs_recurring_token ) ) {
				$order->update_meta_data( '_dibs_recurring_token', $dibs_recurring_token );
				$order->save();
			}
		}
	}

	/**
	 * Maybe change the needs payment for a WooCommerce order.
	 * Used to trigger process_payment for subscription parent orders with a recurring coupon that results in a 0 value order.
	 *
	 * @param bool     $wc_result The result WooCommerce had.
	 * @param WC_Order $order The WooCommerce order.
	 * @param array    $valid_order_statuses The valid order statuses.
	 *
	 * @return bool
	 */
	public function maybe_change_needs_payment( $wc_result, $order, $valid_order_statuses ) {

		// Only change for Nexi Checkout orders.
		if ( ! in_array( $order->get_payment_method(), nets_easy_all_payment_method_ids(), true ) ) {
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
new Nets_Easy_Subscriptions();
