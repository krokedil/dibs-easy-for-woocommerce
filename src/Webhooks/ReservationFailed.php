<?php
namespace Krokedil\Nexi\Webhooks;

use Krokedil\Nexi\Logging\LogMasking;

defined( 'ABSPATH' ) || exit;

/**
 * Adds an order note when Nexi reports a declined payment attempt (payment.reservation.failed).
 *
 * A decline is not final, since the customer can retry in the same Nexi checkout, so the order status is left alone.
 */
class ReservationFailed {
	/**
	 * The query arg that routes the webhook to this handler.
	 */
	const QUERY_ARG = 'nexi-reservation-failed-callback';

	/**
	 * The Nexi event name.
	 */
	const EVENT = 'payment.reservation.failed';

	/**
	 * Order meta with the webhook ids already handled, since Nexi delivers at least once.
	 */
	const HANDLED_META_KEY = '_nexi_reservation_failed_webhook_ids';

	/**
	 * How many handled webhook ids to keep on an order.
	 */
	const MAX_HANDLED_IDS = 20;

	/**
	 * Listen for the webhook on the plugin's existing callback endpoint.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'woocommerce_api_dibs_api_callbacks', array( __CLASS__, 'listen' ) );
	}

	/**
	 * The URL Nexi sends the webhook to.
	 *
	 * @return string
	 */
	public static function get_url() {
		return add_query_arg( array( self::QUERY_ARG => '1' ), get_home_url() . '/wc-api/DIBS_Api_Callbacks/' );
	}

	/**
	 * The credential Nexi sends back in the Authorization header.
	 *
	 * @return string
	 */
	public static function get_authorization() {
		return wp_hash( self::EVENT . '-webhook' );
	}

	/**
	 * Handle the webhook request. Answers 200 unless the payment state could not be read, since Nexi retries anything else.
	 *
	 * @return void
	 */
	public static function listen() {
		if ( '1' !== filter_input( INPUT_GET, self::QUERY_ARG, FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ) {
			return;
		}

		if ( ! self::is_authorized() ) {
			\Nets_Easy_Logger::log( 'Reservation failed webhook rejected: invalid authorization.' );
			status_header( 401 );
			exit;
		}

		$data   = json_decode( file_get_contents( 'php://input' ), true );
		$result = self::handle( is_array( $data ) ? $data : array() );

		status_header( null === $result ? 503 : 200 );
		exit;
	}

	/**
	 * Check the Authorization header against the credential sent when the webhook was registered.
	 *
	 * @return bool
	 */
	private static function is_authorized() {
		$header = '';
		foreach ( array( 'HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION' ) as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$header = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				break;
			}
		}

		return '' !== $header && hash_equals( self::get_authorization(), $header );
	}

	/**
	 * Add a note about the declined attempt to the order the payment belongs to.
	 *
	 * @param array $data The decoded webhook body.
	 * @return bool|null True if a note was added, null if the payment state could not be read and Nexi should retry.
	 */
	public static function handle( $data ) {
		if ( self::EVENT !== ( $data['event'] ?? '' ) || empty( $data['data']['paymentId'] ) || ! is_string( $data['data']['paymentId'] ) ) {
			\Nets_Easy_Logger::log( 'Reservation failed webhook ignored: unexpected payload.' );
			return false;
		}

		\Nets_Easy_Logger::log(
			array(
				'title' => 'Reservation failed webhook listener hit',
				'data'  => LogMasking::mask_response( $data ),
			)
		);

		$payment_id = sanitize_key( $data['data']['paymentId'] );
		$order      = nets_easy_get_order_by_purchase_id( $payment_id );
		if ( ! $order instanceof \WC_Order ) {
			\Nets_Easy_Logger::log( "Reservation failed webhook: no order found for payment $payment_id." );
			return false;
		}

		$order_id = $order->get_id();
		if ( $order->has_status( array( 'on-hold', 'processing', 'completed' ) ) ) {
			\Nets_Easy_Logger::log( "Reservation failed webhook: order $order_id is already paid. No note added." );
			return false;
		}

		$webhook_id  = isset( $data['id'] ) && is_string( $data['id'] ) ? sanitize_key( $data['id'] ) : '';
		$handled_ids = array_filter( (array) $order->get_meta( self::HANDLED_META_KEY ) );
		if ( '' !== $webhook_id && in_array( $webhook_id, $handled_ids, true ) ) {
			\Nets_Easy_Logger::log( "Reservation failed webhook: $webhook_id was already handled for order $order_id." );
			return false;
		}

		// A retry in the same checkout can succeed before this arrives.
		$nexi_order = Nets_Easy()->api->get_nets_easy_order( $payment_id, true );
		if ( is_wp_error( $nexi_order ) ) {
			\Nets_Easy_Logger::log( "Reservation failed webhook: could not read payment $payment_id from Nexi. No note added to order $order_id, Nexi will retry." );
			return null;
		}

		if ( nexi_payment_is_paid( $nexi_order ) ) {
			\Nets_Easy_Logger::log( "Reservation failed webhook: payment $payment_id is reserved in Nexi. No note added to order $order_id." );
			return false;
		}

		if ( '' !== $webhook_id ) {
			$handled_ids[] = $webhook_id;
			$order->update_meta_data( self::HANDLED_META_KEY, array_slice( $handled_ids, -self::MAX_HANDLED_IDS ) );
		}

		$order->add_order_note( self::get_note( $data['data']['error'] ?? array() ) );
		$order->save();

		\Nets_Easy_Logger::log( "Reservation failed webhook: decline noted on order $order_id (payment $payment_id)." );
		return true;
	}

	/**
	 * The order note, with the decline code when Nexi sends one.
	 *
	 * @param mixed $error The error object from the webhook.
	 * @return string
	 */
	private static function get_note( $error ) {
		$error   = is_array( $error ) ? $error : array();
		$code    = isset( $error['code'] ) && is_scalar( $error['code'] ) ? sanitize_text_field( (string) $error['code'] ) : '';
		$message = isset( $error['message'] ) && is_scalar( $error['message'] ) ? sanitize_text_field( (string) $error['message'] ) : '';
		$source  = isset( $error['source'] ) && is_scalar( $error['source'] ) ? sanitize_text_field( (string) $error['source'] ) : '';

		$note = __( 'Nexi Checkout: a payment attempt was declined. Nothing was charged, and the customer can try again.', 'dibs-easy-for-woocommerce' );
		if ( '' === $code ) {
			return $note;
		}

		$reason = trim( substr( $code, 0, 10 ) . ' ' . substr( $message, 0, 100 ) );
		if ( '' !== $source ) {
			/* translators: %1$s: decline code and message, %2$s: where the decline came from, for example Issuer. */
			$reason = sprintf( __( '%1$s (source: %2$s)', 'dibs-easy-for-woocommerce' ), $reason, substr( $source, 0, 30 ) );
		}

		/* translators: %1$s: the note, %2$s: the decline code, message and source from Nexi. */
		return sprintf( __( '%1$s Reason: %2$s.', 'dibs-easy-for-woocommerce' ), $note, $reason );
	}
}
