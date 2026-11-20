<?php
namespace Krokedil\Nexi\Requests;

use WC_Order;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * The Idempotency-Key for a charge or refund, so a retry after a lost response cannot charge twice.
 *
 * The key is derived from the order and an attempt counter in order meta. The counter only moves
 * once Nexi has given a definite answer, so an ambiguous failure is retried under the same key.
 */
class IdempotencyKey {
	/**
	 * Order meta prefix for the attempt counter, suffixed with the action.
	 */
	const META_PREFIX = '_nexi_idempotency_attempt_';

	/**
	 * Nexi's documented maximum key length.
	 */
	const MAX_LENGTH = 63;

	/**
	 * The order the counter is stored on.
	 *
	 * @var WC_Order
	 */
	private $order;

	/**
	 * What is being done, for example "renewal" or "refund".
	 *
	 * @var string
	 */
	private $action;

	/**
	 * The request URL and amount, so a different charge never reuses a key.
	 *
	 * @var string
	 */
	private $fingerprint;

	/**
	 * The key, once built.
	 *
	 * @var string|null
	 */
	private $value;

	/**
	 * Class constructor.
	 *
	 * @param WC_Order $order The order the charge or refund belongs to.
	 * @param string   $action What is being done, for example "renewal" or "refund".
	 * @param string   $request_url The request URL, which holds the payment, charge or subscription id.
	 * @param int      $amount The amount in minor units.
	 */
	public function __construct( $order, $action, $request_url, $amount ) {
		$this->order       = $order;
		$this->action      = $action;
		$this->fingerprint = home_url() . '|' . $request_url . '|' . $amount;
	}

	/**
	 * The key to send in the Idempotency-Key header.
	 *
	 * @return string
	 */
	public function get_value() {
		if ( null === $this->value ) {
			$this->value = substr(
				sprintf(
					'woo-%s-%d-%d-%s',
					$this->action,
					$this->order->get_id(),
					$this->get_attempt(),
					substr( hash( 'sha256', $this->fingerprint ), 0, 16 )
				),
				0,
				self::MAX_LENGTH
			);
		}

		return $this->value;
	}

	/**
	 * Moves to a new key when Nexi gave a definite answer, and keeps it when the outcome is unknown.
	 *
	 * @param array|WP_Error $response The processed response from the request.
	 * @return void
	 */
	public function settle( $response ) {
		if ( self::is_outcome_unknown( $response ) ) {
			$this->order->add_order_note(
				sprintf(
					/* translators: %s: The idempotency key. */
					__( 'No clear answer from Nexi, so the request may have gone through. A retry reuses idempotency key %s, so it cannot be processed twice.', 'dibs-easy-for-woocommerce' ),
					$this->get_value()
				)
			);
			return;
		}

		$this->order->update_meta_data( self::META_PREFIX . $this->action, (string) ( $this->get_attempt() + 1 ) );
		$this->order->save();
	}

	/**
	 * Whether Nexi may have acted on the request without us learning the result.
	 *
	 * Transport errors, 5xx and 409 (a request with the same key still in progress) all count.
	 *
	 * @param array|WP_Error $response The processed response from the request.
	 * @return bool
	 */
	public static function is_outcome_unknown( $response ) {
		if ( ! is_wp_error( $response ) ) {
			return false;
		}

		// The request classes use the HTTP status as the error code, transport errors have a string code.
		$code = $response->get_error_code();
		if ( ! is_int( $code ) ) {
			return true;
		}

		return 409 === $code || $code >= 500;
	}

	/**
	 * The current attempt number for this action on the order.
	 *
	 * @return int
	 */
	private function get_attempt() {
		return absint( $this->order->get_meta( self::META_PREFIX . $this->action ) );
	}
}
