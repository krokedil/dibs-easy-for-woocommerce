<?php
/**
 * Formats the user agent sent to Nets.
 *
 * @package DIBS_Easy/Classes/Requests/Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * DIBS_Requests_User_Agent class.
 *
 * Class that formats the user agent sent to Nets.
 */
class DIBS_Requests_User_Agent extends DIBS_Requests2 {

	/**
	 * Gets formatted user agent.
	 *
	 * @return string
	 */
	public function get() {
		$protocols  = array( 'http://', 'http://www.', 'https://', 'https://www.' );
		$url        = str_replace( $protocols, '', get_bloginfo( 'url' ) );
		$user_agent = apply_filters( 'dibs_easy_http_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; ' . iconv( 'UTF-8', 'ASCII//IGNORE', $url ) ) . ' - Plugin/' . WC_DIBS_EASY_VERSION . ' - PHP/' . phpversion() . ' - Krokedil';
		return $user_agent;
	}
}
