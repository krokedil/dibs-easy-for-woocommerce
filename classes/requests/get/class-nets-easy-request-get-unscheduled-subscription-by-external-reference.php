<?php
/**
 * Get Subscription by External ID request class
 *
 * @package DIBS_Easy/Classes/Requests
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Subscription by External ID request class
 */
class Nets_Easy_Request_Get_Unscheduled_Subscription_By_External_Reference extends Nets_Easy_Request_Get {

	/**
	 * $external_reference.
	 *
	 * @var string
	 */
	public $external_reference;


	/**
	 * Class Constructor.
	 *
	 * @param array $arguments The request args.
	 */
	public function __construct( $arguments ) {
		parent::__construct( $arguments );
		$this->external_reference = $arguments['external_reference'];

	}


	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		return $this->endpoint . 'unscheduledsubscriptions?externalreference=' . $this->external_reference;
	}
}
