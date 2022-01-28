<?php
/**
 * Nets Gateway class
 *
 * @package DIBS_Easy/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Nets Gateway class
 */
class DIBS_Easy_Gateway extends WC_Payment_Gateway {

	/**
	 * Checkout fields.
	 *
	 * @var string
	 */
	public $checkout_fields;

	/**
	 * DIBS_Easy_Gateway constructor.
	 */
	public function __construct() {
		$this->id = 'dibs_easy';

		$this->method_title = __( 'Nets Easy', 'dibs-easy-for-woocommerce' );

		$this->method_description = __( 'Nets Easy Payment for checkout', 'dibs-easy-for-woocommerce' );

		$this->description = $this->get_option( 'description' );

		// Load the form fields.
		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();
		// Get the settings values.
		$this->title         = $this->get_option( 'title' );
		$this->enabled       = $this->get_option( 'enabled' );
		$this->checkout_flow = ( isset( $this->settings['checkout_flow'] ) ) ? $this->settings['checkout_flow'] : 'embedded';

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		$this->supports = array(
			'products',
			'refunds',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'subscription_payment_method_change',
			'multiple_subscriptions',
		);

		// Add class if DIBS Easy is set as the default gateway.
		add_filter( 'body_class', array( $this, 'dibs_add_body_class' ) );
		add_action( 'woocommerce_thankyou_dibs_easy', array( $this, 'dibs_thankyou' ) );
		add_action( 'woocommerce_thankyou', array( $this, 'maybe_delete_dibs_sessions' ), 100, 1 );
	}

	/**
	 * Get gateway icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		$icon_src   = 'https://cdn.dibspayment.com/logo/checkout/combo/horiz/DIBS_checkout_kombo_horizontal_04.png';
		$icon_width = '145';
		$icon_html  = '<img src="' . $icon_src . '" alt="Nets - Payments made easy" style="max-width:' . $icon_width . 'px"/>';
		return apply_filters( 'wc_dibs_easy_icon_html', $icon_html );
	}

	/**
	 * Checks if method should be available.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		return true;
	}

	/**
	 * Init form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include WC_DIBS_PATH . '/includes/dibs-settings.php';
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int  $order_id WooCommerce order ID.
	 * @param  bool $retry WooCommerce Retry.
	 *
	 * @return array
	 */
	public function process_payment( $order_id, $retry = false ) {
		$order = wc_get_order( $order_id );

		// Subscription payment method change.
		$change_payment_method = filter_input( INPUT_GET, 'change_payment_method', FILTER_SANITIZE_STRING );
		if ( ! empty( $change_payment_method ) ) {
			$request  = new DIBS_Requests_Create_DIBS_Order( 'redirect', $order_id );
			$response = json_decode( $request->request() );
			if ( array_key_exists( 'hostedPaymentPageUrl', $response ) ) {
				// All good. Redirect customer to DIBS payment page.
				$order->add_order_note( __( 'Customer redirected to Nets payment page.', 'dibs-easy-for-woocommerce' ) );
				return array(
					'result'   => 'success',
					'redirect' => add_query_arg( 'language', wc_dibs_get_locale(), $response->hostedPaymentPageUrl ), // phpcs:ignore
				);
			} else {
				// Something else went wrong.
				if ( $response->errors ) {
					foreach ( $response->errors as $error ) {
						$error_message = $error[0];
					}
					if ( $this->is_json( $error_message ) ) {
						$error_message = json_decode( $error_message );
					}
				} else {
					$error_message = __( 'An error occured during communication with Nets. Please try again.', 'dibs-easy-for-woocommerce' );
				}
				/* Translators: Error message from Nets. */
				wc_add_notice( sprintf( __( 'Nets Easy error: %s', 'dibs-easy-for-woocommerce' ), wp_json_encode( $error_message ) ), 'error' );
				return false;
			}
		}

		// Regular purchase.
		if ( 'embedded' === $this->checkout_flow && ! is_wc_endpoint_url( 'order-pay' ) ) {
			// Save payment type, card details & run $order->payment_complete() if all looks good.
			if ( ! $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {

				// Update order number in DIBS system if this is the embedded checkout flow.
				$payment_id = get_post_meta( $order_id, '_dibs_payment_id', true );
				$request    = new DIBS_Requests_Update_DIBS_Order_Reference( $payment_id, $order_id );
				$request    = $request->request();

				// Add #dibseasy hash to checkout url so we can respond to DIBS that payment can proceed and be finalized in DIBS system.
				$response = array(
					'return_url' => add_query_arg( 'easy_confirm', 'yes', $this->get_return_url( $order ) ),
					'time'       => time(),
				);
				return array(
					'result'   => 'success',
					'redirect' => '#dibseasy=' . base64_encode( wp_json_encode( $response ) ), // phpcs:ignore
				);
			}
		} else {
			$request  = new DIBS_Requests_Create_DIBS_Order( 'redirect', $order_id );
			$response = json_decode( $request->request(), true );

			if ( array_key_exists( 'hostedPaymentPageUrl', $response ) ) {
				// All good. Redirect customer to DIBS payment page.
				$order->add_order_note( __( 'Customer redirected to Nets payment page.', 'dibs-easy-for-woocommerce' ) );
				update_post_meta( $order_id, '_dibs_payment_id', $response['paymentId'] ); // phpcs:ignore
				return array(
					'result'   => 'success',
					'redirect' => add_query_arg( 'language', wc_dibs_get_locale(), $response['hostedPaymentPageUrl'] ), // phpcs:ignore
				);
			} else {
				// Something else went wrong.
				if ( isset( $response['errors'] ) && ! empty( $response['errors'] ) ) {
					foreach ( $response['errors'] as $error ) {
						$error_message = $error[0];
					}
					if ( $this->is_json( $error_message ) ) {
						$error_message = json_decode( $error_message );
					}
				} else {
					$error_message = __( 'An error occured during communication with Nets. Please try again.', 'dibs-easy-for-woocommerce' );
				}
				/* Translators: Error message from Nets. */
				wc_add_notice( sprintf( __( 'Nets Easy error: %s', 'dibs-easy-for-woocommerce' ), wp_json_encode( $error_message ) ), 'error' );
				return false;
			}
		}
	}

	/**
	 * Process the refund.
	 *
	 * @param  int    $order_id WooCommerce order ID.
	 * @param  string $amount Refund amount.
	 * @param  string $reason Reason test message for the refund.
	 *
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		$request = new DIBS_Request_Refund_Order( $order_id );
		$request = json_decode( $request->request(), true );

		if ( array_key_exists( 'refundId', $request ) ) { // Payment success
			// Translators: Nets refund ID.
			$order->add_order_note( sprintf( __( 'Refund made in Nets Easy with refund ID %1$s. Reason: %2$s', 'dibs-easy-for-woocommerce' ), $request['refundId'], $reason ) ); // phpcs:ignore
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Add Nets Easy body class.
	 *
	 * @param  array $class Body classes.
	 *
	 * @return array
	 */
	public function dibs_add_body_class( $class ) {
		if ( is_checkout() ) {
			$available_payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
			reset( $available_payment_gateways );
			$first_gateway = key( $available_payment_gateways );

			if ( 'dibs_easy' === $first_gateway ) {
				$class[] = 'dibs-selected';
			}
		}
		return $class;
	}

	/**
	 * Nets easy thank you page hook.
	 *
	 * @param  string $order_id WC order id.
	 *
	 * @return void
	 */
	public function dibs_thankyou( $order_id ) {
		$order = wc_get_order( $order_id );

		// Embedded or redirect checkout flow.
		if ( 'embedded' === $this->checkout_flow ) {
			// Save payment type, card details & run $order->payment_complete() if all looks good.
			if ( ! $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
				wc_dibs_confirm_dibs_order( $order_id );
				$order->add_order_note( __( 'Order finalized in thankyou page.', 'dibs-easy-for-woocommerce' ) );
				WC()->cart->empty_cart();
			}

			// Clear sessionStorage.
			echo '<script>sessionStorage.removeItem("DIBSRequiredFields")</script>';
			echo '<script>sessionStorage.removeItem("DIBSFieldData")</script>';

			// Unset sessions.
			wc_dibs_unset_sessions();
		} else {
			if ( ! $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
				wc_dibs_confirm_dibs_order( $order_id );
			}
		}

	}

	/**
	 * Delete Nets sessions.
	 *
	 * @param  string $order_id WC order id.
	 *
	 * @return void
	 */
	public function maybe_delete_dibs_sessions( $order_id ) {
		wc_dibs_unset_sessions();
	}

	/**
	 * Check if data is json.
	 *
	 * @param  string $string Json object.
	 *
	 * @return mixed
	 */
	public function is_json( $string ) {
		json_decode( $string );
		return ( json_last_error() === JSON_ERROR_NONE );
	}

}
