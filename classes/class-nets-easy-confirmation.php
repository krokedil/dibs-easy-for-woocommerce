<?php
/**
 * Confirmation Class file.
 *
 * @package DIBS/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Nets_Easy_Confirmation class.
 *
 * @since 1.17.0
 *
 * Class that handles confirmation of order and redirect to Thank you page.
 */
class Nets_Easy_Confirmation {

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
	 * DIBS_Confirmation constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'maybe_reload_page' ), 1 );
		add_action( 'init', array( $this, 'confirm_order' ), 10, 2 );
		add_action( 'init', array( $this, 'maybe_confirm_customer_redirected_from_payment_page_order' ), 20 );
	}

	/**
	 * Maybe reload the page based on query args.
	 * We do this via JavaScript and use top.location.
	 * This is done since the confirmation url is rendered in the iframe located in the overlay
	 * and we want the customer to come back to the original browser window.
	 *
	 * @return void
	 */
	public function maybe_reload_page() {
		$reload = filter_input( INPUT_GET, 'nets_reload', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! empty( $reload ) ) {
			$url = remove_query_arg( 'nets_reload' );
			?>
			<script>
				top.location = "<?php echo esc_url( $url ); ?>"
			</script>
			<?php
			wp_die();
		}
	}


	/**
	 * Confirm the order in Woo before redirecting the customer to thank you page.
	 */
	public function confirm_order() {

		$easy_confirm = filter_input( INPUT_GET, 'easy_confirm', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$order_key    = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( empty( $easy_confirm ) || empty( $order_key ) ) {
			return;
		}
		$order_id = wc_get_order_id_by_order_key( $order_key );
		$order    = wc_get_order( $order_id );

		Nets_Easy_Logger::log( $order_id . ': Confirmation endpoint hit for order.' );

		if ( empty( $order->get_date_paid() ) ) {

			Nets_Easy_Logger::log( $order_id . ': Confirm the Nexi order from the confirmation page.' );

			// Confirm the order.
			wc_dibs_confirm_dibs_order( $order_id );
			wc_dibs_unset_sessions();
		}
	}

	/**
	 * This function is used when customer is redirected from a payment page.
	 * The main reason for this is when a purchase is done on mobile phone with a non default browser.
	 * Bank ID/Vipps/Swish might then redirect the customer back to the stores checkout page,
	 * but in the default browser. In this scenario it doesn't exist a cart session in WooCommerce.
	 * Instead of trying to display the embedded checkout we grab the payment_id and check the status.
	 * If payment is created, we redirect the customer to the order thankyou page.
	 *
	 * This function is triggered on init - on priority 20.
	 * It needs to be triggered after similar logic in the subscription class (dibs_payment_method_changed).
	 */
	public function maybe_confirm_customer_redirected_from_payment_page_order() {

		$payment_id = filter_input( INPUT_GET, 'paymentId', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( empty( $payment_id ) ) {
			return;
		}

		Nets_Easy_Logger::log( $payment_id . '. Customer redirected back to checkout. Checking payment status.' );

		$request = Nets_Easy()->api->get_nets_easy_order( $payment_id );

		if ( is_wp_error( $request ) ) {
			return;
		}

		if ( isset( $request['payment']['summary']['reservedAmount'] ) || isset( $request['payment']['summary']['chargedAmount'] ) || isset( $request['payment']['subscription']['id'] ) ) {

			$order = nets_easy_get_order_by_purchase_id( $payment_id );

			if ( ! is_object( $order ) ) {
				return;
			}

			Nets_Easy_Logger::log( $payment_id . '. Customer redirected back to checkout. Payment created. Order ID ' . $order->get_id() );

			if ( empty( $order->get_date_paid() ) ) {

				Nets_Easy_Logger::log( $payment_id . '. Order ID ' . $order->get_id() . '. Confirming the order.' );
				// Confirm the order.
				wc_dibs_confirm_dibs_order( $order->get_id() );
				wc_dibs_unset_sessions();
				wp_safe_redirect( html_entity_decode( $order->get_checkout_order_received_url(), ENT_QUOTES ) );
				exit;

			} else {
				Nets_Easy_Logger::log( $payment_id . '. Order ID ' . $order->get_id() . '. Order already confirmed.' );
				return;
			}
		} else {
			Nets_Easy_Logger::log( $payment_id . '. Customer redirected back to checkout. Payment status is NOT paid.' );
		}
	}
}
Nets_Easy_Confirmation::get_instance();
