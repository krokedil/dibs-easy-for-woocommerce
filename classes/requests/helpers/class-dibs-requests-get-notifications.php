<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Requests_Notifications {

	public static function get_notifications() {
		return array(
			'webHooks' => self::get_web_hooks(),
		);
	}

	public static function get_web_hooks() {
		$web_hooks = array();

		// Only set web hooks if host is not local.
		if ( isset( $_SERVER['REMOTE_ADDR'] ) && '127.0.0.1' !== $_SERVER['REMOTE_ADDR'] ) {
			$web_hooks[] = array(
				'eventName'     => 'payment.checkout.completed',
				'url'           => add_query_arg( array( 'dibs-payment-created-callback' => '1' ), get_home_url() . '/wc-api/DIBS_Api_Callbacks/' ),
				'authorization' => wp_create_nonce( 'dibs_web_hooks' ),
			);
		}

		return $web_hooks;
	}
}
