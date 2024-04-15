<?php
/**
 * Logger class file.
 *
 * @package DIBS_Logger/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Logger class.
 */
class Nets_Easy_Logger {
	/**
	 * Log message string
	 *
	 * @var $log
	 */
	public static $log;

	/**
	 * Logs an event.
	 *
	 * @param string|array $data The data string|array.
	 */
	public static function log( $data ) {
		$dibs_easy__settings = get_option( 'woocommerce_dibs_easy_settings' );
		if ( 'yes' === $dibs_easy__settings['debug_mode'] ) {
			$message = self::format_data( $data );
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->add( 'nets_easy', wp_json_encode( $message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		}

		if ( isset( $data['response']['code'] ) && ( $data['response']['code'] < 200 || $data['response']['code'] > 299 ) ) {
			self::log_to_db( $data );
		}
	}

	/**
	 * Logs an event in the WP DB.
	 *
	 * @param array $data The data to be logged.
	 */
	public static function log_to_db( $data ) {
		$logs = get_option( 'dibs_easy_debuglog', array() );

		if ( ! empty( $logs ) ) {
			$logs = json_decode( $logs );
		}

		$logs   = array_slice( $logs, - 14 );
		$logs[] = $data;
		$logs   = wp_json_encode( $logs );
		update_option( 'dibs_easy_debuglog', $logs, false );
	}

	/**
	 * Formats the log data to prevent json error.
	 *
	 * @param string|array $data The data string|array.
	 *
	 * @return string|array
	 */
	public static function format_data( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}
		if ( isset( $data['request']['body'] ) ) {
			$request_body            = json_decode( $data['request']['body'], true );
			$data['request']['body'] = ( ! empty( $request_body ) ) ? $request_body : $data['request']['body'];
		}

		return $data;
	}

	/**
	 * Formats the log data to be logged.
	 *
	 * @param string $checkout_id The gateway Checkout ID.
	 * @param string $method The method.
	 * @param string $title The title for the log.
	 * @param array  $request_args The request args.
	 * @param string $request_url The request url.
	 * @param array  $response The response.
	 * @param string $code The status code.
	 * @return array
	 */
	public static function format_log( $checkout_id, $method, $title, $request_args, $request_url, $response, $code ) {
		return array(
			'id'             => $checkout_id,
			'type'           => $method,
			'title'          => $title,
			'request_url'    => $request_url,
			'request'        => $request_args,
			'response'       => array(
				'body' => $response['body'],
				'code' => $code,
			),
			'timestamp'      => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions -- Date is not used for display.
			'stack'          => self::get_stack(),
			'plugin_version' => WC_DIBS_EASY_VERSION,
		);
	}

	/**
	 * Gets the stack for the request.
	 *
	 * @return array
	 */
	public static function get_stack() {
		$debug_data = debug_backtrace(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions -- Data is not used for display.
		$stack      = array();
		foreach ( $debug_data as $data ) {
			$extra_data = '';
			if ( ! in_array( $data['function'], array( 'get_stack', 'format_log' ), true ) ) {
				if ( in_array( $data['function'], array( 'do_action', 'apply_filters' ), true ) ) {
					if ( isset( $data['object'] ) && $data['object'] instanceof WP_Hook ) {
						$priority   = $data['object']->current_priority();
						$name       = is_array( $data['object']->current() ) ? key( $data['object']->current() ) : '';
						$extra_data = $name . ' : ' . $priority;
					}
				}
			}
			$stack[] = $data['function'] . $extra_data;
		}
		return $stack;
	}
}
