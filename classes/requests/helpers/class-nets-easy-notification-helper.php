<?php
/**
 * Formats the webhook sent to Nets.
 *
 * @package DIBS_Easy/Classes/Requests/Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * DIBS_Requests_Notifications class.
 *
 * Class that formats the webhook information sent to Nets.
 */
class Nets_Easy_Notification_Helper {

	/**
	 * Gets formatted notification.
	 *
	 * @return array
	 */
	public static function get_notifications() {
		return array(
			'webHooks' => self::get_web_hooks(),
		);
	}

	/**
	 * Gets formatted webHooks url.
	 *
	 * @return array
	 */
	public static function get_web_hooks() {
		$web_hooks = array();

		// Do not send webhook url if this site is declared as a local environment via wp_get_environment_type().
		// Read more about wp_get_environment_type https://developer.wordpress.org/reference/functions/wp_get_environment_type/.
		if ( function_exists( 'wp_get_environment_type' ) && apply_filters( 'nets_easy_environment_without_public_url', 'local' ) === wp_get_environment_type() ) {
			return $web_hooks;
		} else {
			$web_hooks[] = array(
				'eventName'     => 'payment.checkout.completed',
				'url'           => esc_url_raw( add_query_arg( array( 'dibs-payment-created-callback' => '1' ), get_home_url() . '/wc-api/DIBS_Api_Callbacks/' ) ),
				'authorization' => wp_create_nonce( 'dibs_web_hooks' ),
			);
			return $web_hooks;
		}
	}
}
