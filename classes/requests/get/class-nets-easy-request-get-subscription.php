<?php
/**
 * Get Subscription request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Subscription request class
 */
class Nets_Easy_Request_Get_Subscription extends Nets_Easy_Request_Get {

	/**
	 * $subscription_id.
	 *
	 * @var string
	 */
	public $subscription_id;


	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request args.
	 */
	public function __construct( $arguments ) {
		parent::__construct( $arguments );
		$this->log_title       = 'Get subscription';
		$this->subscription_id = $arguments['subscription_id'];
	}


	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		return $this->endpoint . 'subscriptions/' . $this->subscription_id;
	}
}
