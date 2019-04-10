<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Requests_Header extends DIBS_Requests2 {
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
