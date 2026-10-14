<?php
namespace Krokedil\Nexi\Logging;

use KrokedilNexiCheckoutDeps\Krokedil\WpApi\FieldMasker;
use KrokedilNexiCheckoutDeps\Krokedil\WpApi\KeyMasker;

defined( 'ABSPATH' ) || exit;

/**
 * What the plugin masks out of its logs.
 *
 * The configured rules describe the Nexi payloads we know about. The key names are the
 * safety net for everything else that reaches a log entry, including the stack trace.
 */
class LogMasking {
	/**
	 * The address fields kept readable, since a rejected address is a common support case
	 * and none of these identify a person on their own.
	 */
	const ADDRESS_KEPT = array( 'postalCode', 'city', 'country' );

	/**
	 * Key names masked wherever they appear, on top of the package defaults.
	 *
	 * @var string[]
	 */
	private static $key_names = array(
		'email',
		'phone',
		'firstName',
		'lastName',
		'addressLine',
		'receiverLine',
		'dateOfBirth',
		'registrationNumber',
		// The return and cancel URLs carry the order key, which grants access to the order.
		'returnUrl',
		'cancelUrl',
		// Whoever holds the hosted payment page URL can pay with it.
		'hostedPaymentPageUrl',
		// The invoice PDF holds the customer details.
		'pdfLink',
	);

	/**
	 * Widen the package key name masking with the names Nexi uses.
	 *
	 * @return void
	 */
	public static function register() {
		KeyMasker::add_keys( self::$key_names );
	}

	/**
	 * The rules for a Nexi request or response body.
	 *
	 * @return array
	 */
	public static function body_fields() {
		return array(
			'consumer'             => array(
				'keep'            => array( 'shippingAddress', 'billingAddress' ),
				'shippingAddress' => array( 'keep' => self::ADDRESS_KEPT ),
				'billingAddress'  => array( 'keep' => self::ADDRESS_KEPT ),
			),
			'returnUrl'            => 'mask',
			'cancelUrl'            => 'mask',
			'hostedPaymentPageUrl' => 'mask',
			'pdfLink'              => 'mask',
		);
	}

	/**
	 * The rules for a whole set of request args.
	 *
	 * @return array
	 */
	public static function request_fields() {
		return array(
			'headers' => array( 'Authorization' ),
			'body'    => self::body_fields(),
		);
	}

	/**
	 * Mask a set of request args.
	 *
	 * @param array $request_args The request args.
	 * @return array|string The masked args, or the failure marker.
	 */
	public static function mask_request( $request_args ) {
		try {
			// Decode the body that was really sent, so the rules can reach into it.
			if ( isset( $request_args['body'] ) && is_string( $request_args['body'] ) ) {
				$decoded              = json_decode( $request_args['body'], true );
				$request_args['body'] = is_array( $decoded ) ? $decoded : $request_args['body'];
			}

			return FieldMasker::mask( $request_args, self::request_fields() );
		} catch ( \Throwable $e ) {
			return KeyMasker::FAILED;
		}
	}

	/**
	 * Mask a decoded response body.
	 *
	 * @param array|null $body The decoded response body.
	 * @return array|string|null The masked body, or the failure marker.
	 */
	public static function mask_response( $body ) {
		if ( empty( $body ) || ! is_array( $body ) ) {
			return $body;
		}

		try {
			return FieldMasker::mask( $body, self::body_fields() );
		} catch ( \Throwable $e ) {
			return KeyMasker::FAILED;
		}
	}

	/**
	 * Mask a finished log entry by key name.
	 *
	 * @param mixed $data The log entry.
	 * @return mixed The masked entry, or the failure marker.
	 */
	public static function mask_entry( $data ) {
		// A failure here costs the entry, it never lets an unmasked one through.
		try {
			return KeyMasker::mask( $data );
		} catch ( \Throwable $e ) {
			return KeyMasker::FAILED;
		}
	}
}
