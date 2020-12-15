<?php
/**
 * Formats the request header sent to Nets.
 *
 * @package DIBS_Easy/Classes/Requests/Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * DIBS_Requests_Header class.
 *
 * Class that formats the request header sent to Nets.
 */
class DIBS_Requests_Header extends DIBS_Requests2 {

	/**
	 * Gets formatted header.
	 *
	 * @param  string $order_id WooCommerce order ID.
	 *
	 * @return array
	 */
	public function get( $order_id = null ) {
		$formatted_request_header = array(
			'Content-type'        => 'application/json',
			'Accept'              => 'application/json',
			'Authorization'       => apply_filters( 'dibs_easy_request_secret_key', $this->key, $this->testmode, $order_id ),
			'commercePlatformTag' => 'WooEasyKrokedil',
		);
		return $formatted_request_header;
	}
}
