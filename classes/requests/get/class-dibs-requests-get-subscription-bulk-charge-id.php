<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 *
 */
class DIBS_Request_Get_Subscription_Bulk_Id extends Dibs_Request_Get {

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
