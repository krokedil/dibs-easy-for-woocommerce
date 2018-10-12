<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class DIBS_Requests_Header extends DIBS_Requests2 {
	public function get() {
		$formatted_request_header = array(
			'Content-type'  => 'application/json',
			'Accept'        => 'application/json',
			'Authorization' => $this->key,
		);
		return $formatted_request_header;
	}
}
