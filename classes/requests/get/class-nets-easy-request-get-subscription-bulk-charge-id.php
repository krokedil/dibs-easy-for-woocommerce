<?php
/**
 * Nets_Easy_Request_Get_Subscription_Bulk_Charge_Id class.
 *
 * @package
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Get Subscription Bulk Charge ID class.
 */
class Nets_Easy_Request_Get_Subscription_Bulk_Charge_Id extends Nets_Easy_Request_Get {

	/**
	 * The bulk id.
	 *
	 * @var mixed
	 */
	public $bulk_id;

	/**
	 * Class constructor.
	 *
	 * @param array $arguments The request args.
	 */
	public function __construct( $arguments ) {
		parent::__construct( $arguments );
		$this->bulk_id = $arguments['bulk_id'];
	}


	/**
	 * Get the request url.
	 *
	 * @return string
	 */
	protected function get_request_url() {
		return $this->endpoint . 'subscriptions/charges/' . $this->bulk_id;
	}
}
